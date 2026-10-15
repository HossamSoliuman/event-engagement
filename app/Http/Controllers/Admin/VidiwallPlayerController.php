<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderVidiwallPlaylistRequest;
use App\Http\Requests\UpdateVidiwallLoopRequest;
use App\Http\Requests\UpdateVidiwallPhotoSecondsRequest;
use App\Http\Requests\UpdateVidiwallPhotosPerScreenRequest;
use App\Http\Requests\UpdateVidiwallQrScreenRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\VidiwallPlayer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as BaseCollection;

/**
 * The operator's remote for the vidiwall: transport controls plus the playlist
 * of approved uploads. Every action answers with the same snapshot the page
 * polls, so the controls update the moment they are pressed.
 */
class VidiwallPlayerController extends Controller
{
    public function index(Event $event): View
    {
        return view($this->indexView(), [
            'event' => $event,
            'routePrefix' => $this->routePrefix(),
            'counts' => $event->fotoStatusCounts(),
            'snapshot' => $this->snapshot($event),
        ]);
    }

    public function status(Request $request, Event $event): JsonResponse
    {
        return $this->respond($request, $event);
    }

    public function play(Request $request, Event $event): JsonResponse
    {
        if (! $event->player()->play()) {
            return $this->respond($request, $event, 'Nothing to play yet. Approve photos or videos first.', 422);
        }

        ActivityLog::record('vidiwall.played', [], $event->id);

        return $this->respond($request, $event);
    }

    public function pause(Request $request, Event $event): JsonResponse
    {
        $event->player()->pause();
        ActivityLog::record('vidiwall.paused', [], $event->id);

        return $this->respond($request, $event);
    }

    public function stop(Request $request, Event $event): JsonResponse
    {
        $event->player()->stop();
        ActivityLog::record('vidiwall.stopped', [], $event->id);

        return $this->respond($request, $event);
    }

    public function next(Request $request, Event $event): JsonResponse
    {
        $event->player()->next();

        return $this->respond($request, $event);
    }

    public function previous(Request $request, Event $event): JsonResponse
    {
        $event->player()->previous();

        return $this->respond($request, $event);
    }

    public function loop(UpdateVidiwallLoopRequest $request, Event $event): JsonResponse
    {
        $event->player()->setLoop($request->boolean('loop'));

        return $this->respond($request, $event);
    }

    public function qr(UpdateVidiwallQrScreenRequest $request, Event $event): JsonResponse
    {
        $event->player()->setQrScreen($request->boolean('qr'));
        ActivityLog::record($request->boolean('qr') ? 'vidiwall.qr_shown' : 'vidiwall.qr_hidden', [], $event->id);

        return $this->respond($request, $event);
    }

    public function photoSeconds(UpdateVidiwallPhotoSecondsRequest $request, Event $event): JsonResponse
    {
        $event->player()->setPhotoSeconds($request->integer('seconds'));
        ActivityLog::record('vidiwall.photo_seconds_changed', ['seconds' => $request->integer('seconds')], $event->id);

        return $this->respond($request, $event);
    }

    public function photosPerScreen(UpdateVidiwallPhotosPerScreenRequest $request, Event $event): JsonResponse
    {
        $event->player()->setPhotosPerScreen($request->integer('count'));
        ActivityLog::record('vidiwall.photos_per_screen_changed', ['count' => $request->integer('count')], $event->id);

        return $this->respond($request, $event);
    }

    public function playFrom(Request $request, Event $event, FotoUpload $fotoUpload): JsonResponse
    {
        if (! $fotoUpload->isApproved()) {
            return $this->respond($request, $event, 'That item is no longer approved.', 422);
        }

        $event->player()->playFrom($fotoUpload);
        ActivityLog::record('foto.pushed_to_screen', ['foto_id' => $fotoUpload->id, 'uploader' => $fotoUpload->uploader_name], $event->id);

        return $this->respond($request, $event);
    }

    public function playNext(Request $request, Event $event, FotoUpload $fotoUpload): JsonResponse
    {
        if (! $fotoUpload->isApproved()) {
            return $this->respond($request, $event, 'That item is no longer approved.', 422);
        }

        $event->player()->playNext($fotoUpload);

        return $this->respond($request, $event);
    }

    public function reorder(ReorderVidiwallPlaylistRequest $request, Event $event): JsonResponse
    {
        $event->player()->reorder($request->orderedIds());

        return $this->respond($request, $event);
    }

    protected function indexView(): string
    {
        return 'admin.vidiwall-player.index';
    }

    protected function routePrefix(): string
    {
        return 'admin.';
    }

    /**
     * Answers with the snapshot. The page sends back the `version` it last
     * rendered (`v`), and the playlist is only included when it changed.
     */
    private function respond(Request $request, Event $event, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json($this->snapshot($event, (string) $request->input('v', ''), $message), $status);
    }

    /**
     * The player and playlist as the page renders them; the playlist is left
     * out when it still matches `$knownVersion`. `now_playing` is the first
     * item of the page on screen, with the whole page alongside.
     *
     * @return array<string, mixed>
     */
    private function snapshot(Event $event, string $knownVersion = '', ?string $message = null): array
    {
        $player = $event->syncPlayer();
        $page = $player ? $player->screenFotos() : new Collection;
        $nowPlaying = $page->first();
        $playlist = $event->playlist()->toBase()->get(['id', 'displayed_at', 'media_type', 'video_duration']);
        $photoSeconds = (int) ($event->vidiwall_photo_seconds ?: FotoUpload::SCREEN_SECONDS);
        $photosPerScreen = $event->photosPerScreen();
        $version = md5($photoSeconds.'|'.$photosPerScreen.'|'.$playlist->map(fn (object $row) => $row->id.':'.$row->displayed_at)->implode(','));

        $payload = [
            'message' => $message,
            'player' => [
                'state' => $player?->state ?? VidiwallPlayer::STATE_STOPPED,
                'loop' => (bool) $player?->loop,
                'qr_screen' => (bool) $player?->qr_screen,
                'resume_after_qr' => (bool) $player?->resume_after_qr,
                'photo_seconds' => $photoSeconds,
                'photos_per_screen' => $photosPerScreen,
                'revision' => $player?->revision ?? 0,
                'elapsed_ms' => $nowPlaying ? $player->elapsedMs() : 0,
            ],
            'now_playing' => $nowPlaying ? [
                ...$this->item($nowPlaying),
                'page_ids' => $page->modelKeys(),
                'page' => $page->map(fn (FotoUpload $foto) => $this->item($foto))->values()->all(),
            ] : null,
            'counts' => [
                'total' => $playlist->count(),
                'unplayed' => $playlist->whereNull('displayed_at')->count(),
                ...$event->fotoStatusCounts(),
            ],
            'run_ms' => $this->runMs($playlist, $photoSeconds, $photosPerScreen),
            'version' => $version,
        ];

        if ($version !== $knownVersion) {
            $payload['items'] = $event->playlist()->get()
                ->each->setRelation('event', $event)
                ->map(fn (FotoUpload $foto) => $this->item($foto))
                ->values()
                ->all();
        }

        return $payload;
    }

    /**
     * Roughly how long one run through the playlist from the top takes: every
     * screenful of photos holds for the photo slot and every video for its clip.
     *
     * @param  BaseCollection<int, object>  $playlist
     */
    private function runMs(BaseCollection $playlist, int $photoSeconds, int $photosPerScreen): int
    {
        $runMs = 0;
        $photosInARow = 0;

        foreach ($playlist as $row) {
            if ($row->media_type !== 'video') {
                $photosInARow++;

                continue;
            }

            $runMs += (int) ceil($photosInARow / $photosPerScreen) * $photoSeconds * 1000;
            $runMs += (int) round(((float) $row->video_duration ?: $photoSeconds) * 1000);
            $photosInARow = 0;
        }

        return $runMs + (int) ceil($photosInARow / $photosPerScreen) * $photoSeconds * 1000;
    }

    /**
     * @return array{id: int, uploader: string, is_video: bool, duration: float|null, slot_ms: int, thumb_url: string|null, video_url: string|null, played_ms: int|null}
     */
    private function item(FotoUpload $foto): array
    {
        return [
            'id' => $foto->id,
            'uploader' => $foto->uploader_name ?: 'Anonymous',
            'is_video' => $foto->isVideo(),
            'duration' => $foto->video_duration ? round((float) $foto->video_duration, 1) : null,
            'slot_ms' => $foto->screenSlotMs(),
            'thumb_url' => $foto->isVideo() ? null : $foto->thumbnail_url,
            'video_url' => $foto->isVideo() ? ($foto->video_url ?? $foto->file_url) : null,
            'played_ms' => $foto->displayed_at?->getTimestampMs(),
        ];
    }
}
