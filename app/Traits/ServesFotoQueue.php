<?php

namespace App\Traits;

use App\Models\Event;
use App\Models\FotoUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The Foto Queue page for admins and moderators. The page loads one tab and then
 * runs without reloads: tabs and further pages come back as rendered cards, the
 * moderation actions answer with fresh counts, and a poll keeps it live.
 */
trait ServesFotoQueue
{
    /**
     * The Foto Queue page, or one page of a tab as JSON for tab switches and infinite scroll.
     */
    protected function showFotoQueue(Request $request, Event $event, string $view): View|JsonResponse
    {
        $status = $this->fotoQueueStatus($request);

        $event->syncPlayer();

        [$fotos, $hasMore] = $this->fotoQueuePage($event, $status, $request->integer('before') ?: null);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $status,
                'cards' => $this->renderFotoCards($fotos),
                'has_more' => $hasMore,
                'counts' => $event->fotoStatusCounts(),
                'cursor' => now()->getTimestamp(),
            ]);
        }

        return view($view, [
            'event' => $event,
            'status' => $status,
            'fotos' => $fotos,
            'hasMore' => $hasMore,
            'counts' => $event->fotoStatusCounts(),
            'onScreen' => $event->onScreenFoto(),
            'queued' => $event->getQueuedFotosCount(),
        ]);
    }

    /**
     * Live snapshot polled by the page: counts, what is on screen, the state of
     * the cards shown (`ids`) and the cards of the open tab (`status`) that
     * changed since the last poll (`since`, the `cursor` it was given).
     */
    protected function fotoQueueSnapshot(Request $request, Event $event): JsonResponse
    {
        $ids = array_slice(array_values(array_filter(array_map('intval', explode(',', (string) $request->query('ids', ''))))), 0, 300);

        $event->syncPlayer();

        $snapshot = $event->screenQueueStatus($ids);

        if ($request->filled('since')) {
            $changed = $event->fotoUploads()
                ->where('status', $this->fotoQueueStatus($request))
                ->where('updated_at', '>=', Carbon::createFromTimestamp($request->integer('since') - 2))
                ->orderByDesc('id')
                ->limit(60)
                ->get()
                ->each->setRelation('event', $event);

            $snapshot['changed'] = $this->renderFotoCards($changed);
        }

        $snapshot['cursor'] = now()->getTimestamp();

        return response()->json($snapshot);
    }

    /**
     * Answers a moderation action: fresh tab counts for the page script, or a
     * redirect with a flash message for plain form posts.
     */
    protected function fotoActionResponse(Request $request, Event $event, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'counts' => $event->fotoStatusCounts(),
            ]);
        }

        return back()->with('success', $message);
    }

    protected function fotoQueueStatus(Request $request): string
    {
        $status = (string) $request->query('status', 'pending');

        return in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'pending';
    }

    /**
     * Newest first; `$beforeId` continues below the last card shown.
     *
     * @return array{0: Collection<int, FotoUpload>, 1: bool}
     */
    protected function fotoQueuePage(Event $event, string $status, ?int $beforeId = null): array
    {
        $perPage = 40;

        $fotos = $event->fotoUploads()
            ->where('status', $status)
            ->when($beforeId, fn (Builder $query) => $query->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit($perPage + 1)
            ->get();

        $page = $fotos->take($perPage)->each->setRelation('event', $event);

        return [$page, $fotos->count() > $perPage];
    }

    /**
     * @param  Collection<int, FotoUpload>  $fotos
     * @return list<array{id: int, html: string}>
     */
    protected function renderFotoCards(Collection $fotos): array
    {
        return $fotos->map(fn (FotoUpload $foto) => [
            'id' => $foto->id,
            'html' => view('admin.fotos._card', ['foto' => $foto])->render(),
        ])->values()->all();
    }
}
