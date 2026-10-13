<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderVidiwallPlaylistRequest;
use App\Http\Requests\UpdateVidiwallLoopRequest;
use App\Http\Requests\UpdateVidiwallPhotoSecondsRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\VidiwallPlayer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function photoSeconds(UpdateVidiwallPhotoSecondsRequest $request, Event $event): JsonResponse
    {
        $event->player()->setPhotoSeconds($request->integer('seconds'));
        ActivityLog::record('vidiwall.photo_seconds_changed', ['seconds' => $request->integer('seconds')], $event->id);

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
     * out when it still matches `$knownVersion`.
     *
     * @return array<string, mixed>
     */
    private function snapshot(Event $event, string $knownVersion = '', ?string $message = null): array
    {
        $player = $event->syncPlayer();
        $nowPlaying = $player?->screenFoto();
        $playlist = $event->playlist()->toBase()->get(['id', 'displayed_at']);
        $photoSeconds = (int) ($event->vidiwall_photo_seconds ?: FotoUpload::SCREEN_SECONDS);
        $version = md5($photoSeconds.'|'.$playlist->map(fn (object $row) => $row->id.':'.$row->displayed_at)->implode(','));

        $payload = [
            'message' => $message,
            'player' => [
                'state' => $player?->state ?? VidiwallPlayer::STATE_STOPPED,
                'loop' => (bool) $player?->loop,
                'photo_seconds' => $photoSeconds,
                'revision' => $player?->revision ?? 0,
                'elapsed_ms' => $nowPlaying ? $player->elapsedMs() : 0,
            ],
            'now_playing' => $nowPlaying ? $this->item($nowPlaying) : null,
            'counts' => [
                'total' => $playlist->count(),
                'unplayed' => $playlist->whereNull('displayed_at')->count(),
                ...$event->fotoStatusCounts(),
            ],
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
