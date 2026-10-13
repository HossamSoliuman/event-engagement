<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DrawLotteryWinnerRequest;
use App\Http\Requests\UpdateLotteryScreenSettingsRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\LotteryEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The lottery console: the entries, the winners drawn so far (one draw per
 * prize) and the controls for running the draw on the vidiwall. Screen
 * controls answer with the show's status when called from the page's script.
 */
class LotteryAdminController extends Controller
{
    private const DRAW_RUNNING = 'A draw is still running on the vidiwall. Reveal it or hide it first.';

    private const VOTE_REVEALING = 'The vote winner is still being revealed on the vidiwall. Wait for it or hide it first.';

    public function index(Event $event, Request $request): View
    {
        $search = $request->get('search');
        $entries = $event->lotteryEntries()
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%$search%")->orWhere('phone', 'like', "%$search%")))
            ->latest()
            ->paginate(30);

        $winners = $event->lotteryWinners()->get();
        $totalCount = $event->lotteryEntries()->count();

        $lotteryExtraKeys = [];
        foreach ($entries as $entry) {
            if (! empty($entry->extra_fields) && is_array($entry->extra_fields)) {
                $lotteryExtraKeys = array_keys($entry->extra_fields);
                break;
            }
        }

        return view($this->indexView(), [
            'event' => $event,
            'entries' => $entries,
            'winners' => $winners,
            'totalCount' => $totalCount,
            'remainingCount' => $totalCount - $winners->count(),
            'search' => $search,
            'lotteryExtraKeys' => $lotteryExtraKeys,
            'screen' => $event->lotteryShow(),
            'screenStatus' => $this->screenStatus($event),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    /**
     * Draws one more winner; earlier winners keep their places. `next_rank` is
     * the winner number the form was showing, so a form sent twice draws once.
     */
    public function draw(DrawLotteryWinnerRequest $request, Event $event): RedirectResponse
    {
        $screen = $event->lotteryShow();
        $onScreen = $this->wantsScreen($request, $event);

        if ($onScreen && ($busy = $this->screenBusy($event))) {
            return $this->backToLottery($event)->withInput()->with('error', $busy);
        }

        $nextRank = $request->filled('next_rank') ? $request->integer('next_rank') : null;
        $winner = $event->drawLotteryWinner($request->prize(), $nextRank);

        if (! $winner) {
            return $this->backToLottery($event)->withInput()->with('error', match (true) {
                ! $event->lotteryEntries()->exists() => 'No entries to draw from.',
                ! $event->lotteryEntries()->where('is_winner', false)->exists() => 'Everyone in the lottery has already won.',
                default => "Winner #{$nextRank} has already been drawn. Check the winners before drawing again.",
            });
        }

        if ($onScreen) {
            $screen->startDraw($winner);
        }

        ActivityLog::record('lottery.drawn', ['name' => $winner->name, 'rank' => $winner->winner_rank, 'prize' => $winner->prize], $event->id);

        return $this->backToLottery($event)
            ->with('winner_drawn', $winner->id)
            ->with('success', $onScreen
                ? "Winner #{$winner->winner_rank} drawn: {$winner->name}. Revealing on the vidiwall now."
                : "🎉 Winner #{$winner->winner_rank} drawn: {$winner->name}!");
    }

    /**
     * Replaces a winner with a fresh draw for the same prize; the old winner
     * goes back into the draw.
     */
    public function redraw(DrawLotteryWinnerRequest $request, Event $event, LotteryEntry $lotteryEntry): RedirectResponse
    {
        if (! $lotteryEntry->is_winner) {
            return $this->backToLottery($event)->with('error', "{$lotteryEntry->name} is no longer a winner.");
        }

        $screen = $event->lotteryShow();
        $onScreen = $this->wantsScreen($request, $event);

        if ($onScreen && ($busy = $this->screenBusy($event))) {
            return $this->backToLottery($event)->with('error', $busy);
        }

        $replacement = $event->redrawLotteryWinner($lotteryEntry);

        if (! $replacement) {
            return $this->backToLottery($event)->with('error', "There is no one left in the draw to replace {$lotteryEntry->name}.");
        }

        if ($onScreen) {
            $screen->startDraw($replacement);
        }

        ActivityLog::record('lottery.redrawn', ['name' => $replacement->name, 'replaced' => $lotteryEntry->name, 'rank' => $replacement->winner_rank], $event->id);

        return $this->backToLottery($event)
            ->with('winner_drawn', $replacement->id)
            ->with('success', "Winner #{$replacement->winner_rank} redrawn: {$replacement->name} replaces {$lotteryEntry->name}.");
    }

    /**
     * Puts a winner back into the draw. Their prize is kept in the draw form so
     * it can be drawn again straight away.
     */
    public function release(Event $event, LotteryEntry $lotteryEntry): RedirectResponse
    {
        if (! $lotteryEntry->is_winner) {
            return $this->backToLottery($event)->with('error', "{$lotteryEntry->name} is no longer a winner.");
        }

        $prize = $lotteryEntry->prize;
        $event->releaseLotteryWinner($lotteryEntry);

        ActivityLog::record('lottery.released', ['name' => $lotteryEntry->name], $event->id);

        return $this->backToLottery($event)
            ->withInput(['prize' => $prize])
            ->with('success', "{$lotteryEntry->name} is back in the draw.");
    }

    public function reset(Event $event): RedirectResponse
    {
        $event->resetLottery();
        ActivityLog::record('lottery.reset', [], $event->id);

        return back()->with('success', 'Lottery reset. You can draw again.');
    }

    public function destroy(LotteryEntry $entry): RedirectResponse
    {
        return $this->removeEntry($entry);
    }

    public function updateScreen(UpdateLotteryScreenSettingsRequest $request, Event $event): RedirectResponse
    {
        $event->lotteryShow()->update($request->settings());

        return redirect()->to(route($this->routePrefix().'lottery.index', $event).'#vidiwall-show')
            ->with('success', 'Vidiwall show settings saved.');
    }

    public function screen(Event $event): JsonResponse
    {
        return response()->json($this->screenStatus($event));
    }

    public function showWinner(Request $request, Event $event, LotteryEntry $lotteryEntry): JsonResponse|RedirectResponse
    {
        if (! $lotteryEntry->is_winner) {
            return $this->screenResponse($request, $event, "{$lotteryEntry->name} is no longer a winner.", 422);
        }

        $screen = $event->lotteryShow();

        if ($busy = $this->screenBusy($event)) {
            return $this->screenResponse($request, $event, $busy, 409);
        }

        $screen->showWinner($lotteryEntry);

        return $this->screenResponse($request, $event, "{$lotteryEntry->name} is on the vidiwall.");
    }

    public function showBoard(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        if (! $event->lotteryWinners()->exists()) {
            return $this->screenResponse($request, $event, 'Draw a winner before showing the winners.', 422);
        }

        $screen = $event->lotteryShow();

        if ($busy = $this->screenBusy($event)) {
            return $this->screenResponse($request, $event, $busy, 409);
        }

        $screen->showBoard();

        return $this->screenResponse($request, $event, 'The winners are on the vidiwall.');
    }

    public function revealNow(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->lotteryShow()->revealNow();

        return $this->screenResponse($request, $event);
    }

    public function hideScreen(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->lotteryShow()->hide();

        return $this->screenResponse($request, $event, 'The lottery is off the vidiwall.');
    }

    public function export(Event $event): Response
    {
        $entries = $event->lotteryEntries()->get();

        // Collect all extra field keys across all entries
        $extraKeys = [];
        foreach ($entries as $e) {
            if (! empty($e->extra_fields)) {
                foreach (array_keys($e->extra_fields) as $k) {
                    if (! in_array($k, $extraKeys)) {
                        $extraKeys[] = $k;
                    }
                }
            }
        }

        $quote = fn (mixed $value): string => '"'.str_replace('"', '""', (string) $value).'"';

        $headers = array_merge(['ID', 'Name', 'Phone', 'Email'], $extraKeys, ['Winner', 'Winner #', 'Prize', 'Entry Time']);
        $csv = implode(',', array_map($quote, $headers))."\n";

        foreach ($entries as $e) {
            $row = [
                $e->id,
                $quote($e->name),
                $quote($e->phone),
                $quote($e->email ?? ''),
            ];
            foreach ($extraKeys as $k) {
                $row[] = $quote($e->extra_fields[$k] ?? '');
            }
            $row[] = $e->is_winner ? 'YES' : 'No';
            $row[] = $e->is_winner ? $e->winner_rank : '';
            $row[] = $quote($e->prize ?? '');
            $row[] = $e->created_at->format('Y-m-d H:i');
            $csv .= implode(',', $row)."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"lottery-{$event->slug}.csv\"",
        ]);
    }

    protected function indexView(): string
    {
        return 'admin.lottery.index';
    }

    protected function routePrefix(): string
    {
        return 'admin.';
    }

    protected function removeEntry(LotteryEntry $entry): RedirectResponse
    {
        $event = $entry->event;
        $wasWinner = $entry->is_winner;

        $entry->delete();

        if ($wasWinner) {
            $event->renumberLotteryWinners();
        }

        return back()->with('success', 'Entry removed.');
    }

    /**
     * Whether this draw runs on the vidiwall. The form always sends its choice,
     * which is kept as the default for the next draw.
     */
    private function wantsScreen(Request $request, Event $event): bool
    {
        $screen = $event->lotteryShow();

        if (! $request->has('on_screen')) {
            return $screen->draw_on_screen;
        }

        if ($request->boolean('on_screen') !== $screen->draw_on_screen) {
            $screen->update(['draw_on_screen' => $request->boolean('on_screen')]);
        }

        return $request->boolean('on_screen');
    }

    /**
     * Why nothing new may take the screen right now: a winner still on the
     * way, from the lottery or the vote.
     */
    private function screenBusy(Event $event): ?string
    {
        return match (true) {
            $event->lotteryShow()->isRevealPending() => self::DRAW_RUNNING,
            (bool) $event->votingScreen?->setRelation('event', $event)->isRevealPending() => self::VOTE_REVEALING,
            default => null,
        };
    }

    private function backToLottery(Event $event): RedirectResponse
    {
        return redirect()->route($this->routePrefix().'lottery.index', $event);
    }

    private function screenResponse(Request $request, Event $event, ?string $message = null, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([...$this->screenStatus($event), 'message' => $message], $status);
        }

        return $this->backToLottery($event)->with($status < 400 ? 'success' : 'error', $message);
    }

    /**
     * The show on the vidiwall plus a fingerprint of the winners, so an open
     * console notices a draw made from another one.
     *
     * @return array<string, mixed>
     */
    private function screenStatus(Event $event): array
    {
        $winners = $event->lotteryWinners()->toBase()->get(['id', 'winner_rank', 'prize']);

        return [
            ...$event->lotteryShow()->controlStatus(),
            'winners_version' => md5($winners->map(fn (object $winner) => $winner->id.':'.$winner->winner_rank.':'.$winner->prize)->implode('|')),
        ];
    }
}
