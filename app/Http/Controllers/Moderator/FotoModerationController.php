<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ArchivedFotoUpload;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Traits\ExportsFotoUploads;
use App\Traits\ServesFotoArchive;
use App\Traits\ServesFotoQueue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FotoModerationController extends Controller
{
    use ExportsFotoUploads, ServesFotoArchive, ServesFotoQueue;

    public function index(Event $event, Request $request): View|JsonResponse
    {
        return $this->showFotoQueue($request, $event, 'moderator.fotos.index');
    }

    public function status(Event $event, Request $request): JsonResponse
    {
        return $this->fotoQueueSnapshot($request, $event);
    }

    public function archived(Request $request, Event $event): View|RedirectResponse
    {
        return $this->showFotoArchive($request, $event, 'moderator.fotos.archive');
    }

    public function archive(Event $event): RedirectResponse
    {
        return $this->archiveFotoQueue($event);
    }

    public function restoreArchived(Request $request, Event $event, ArchivedFotoUpload $archivedFotoUpload): JsonResponse|RedirectResponse
    {
        return $this->restoreArchivedFoto($request, $event, $archivedFotoUpload);
    }

    public function restoreArchive(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        return $this->restoreFotoArchive($request, $event);
    }

    public function approve(Request $request, Event $event, FotoUpload $foto): JsonResponse|RedirectResponse
    {
        $foto->approve(auth()->id());
        ActivityLog::record('foto.approved', ['foto_id' => $foto->id, 'uploader' => $foto->uploader_name], $foto->event_id);

        return $this->fotoActionResponse($request, $event, 'Approved and added to the playlist.');
    }

    public function reject(Request $request, Event $event, FotoUpload $foto): JsonResponse|RedirectResponse
    {
        $foto->reject($request->input('note'));
        ActivityLog::record('foto.rejected', ['foto_id' => $foto->id], $foto->event_id);

        return $this->fotoActionResponse($request, $event, 'Rejected.');
    }

    public function removeFromScreen(Request $request, Event $event, FotoUpload $foto): JsonResponse|RedirectResponse
    {
        $foto->removeFromScreen();
        ActivityLog::record('foto.removed_from_screen', ['foto_id' => $foto->id], $foto->event_id);

        return $this->fotoActionResponse($request, $event, 'Removed from screen.');
    }

    public function destroy(Request $request, Event $event, FotoUpload $foto): JsonResponse|RedirectResponse
    {
        Storage::disk('media')->delete($foto->file_path);
        if ($foto->thumbnail_path) {
            Storage::disk('media')->delete($foto->thumbnail_path);
        }
        if ($foto->video_path && $foto->video_path !== $foto->file_path) {
            Storage::disk('media')->delete($foto->video_path);
        }
        $foto->delete();
        ActivityLog::record('foto.deleted', ['foto_id' => $foto->id], $foto->event_id);

        return $this->fotoActionResponse($request, $event, 'Deleted.');
    }

    public function export(Event $event): Response
    {
        return $this->fotoUploadsCsv($event);
    }

    public function downloadAll(Event $event): BinaryFileResponse|RedirectResponse
    {
        return $this->fotoUploadsZip($event);
    }
}
