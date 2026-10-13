<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Traits\ServesFotoQueue;
use App\Traits\StagesMediaFiles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FotoModerationController extends Controller
{
    use ServesFotoQueue, StagesMediaFiles;

    public function index(Event $event, Request $request): View|JsonResponse
    {
        return $this->showFotoQueue($request, $event, 'moderator.fotos.index');
    }

    public function status(Event $event, Request $request): JsonResponse
    {
        return $this->fotoQueueSnapshot($request, $event);
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

    public function export(Event $event)
    {
        $fotos = $event->fotoUploads()->get();
        $onScreenId = $event->onScreenFotoId();
        $csv = "ID,Uploader Name,Phone,Media Type,Duration (s),Status,On Screen,Uploaded At\n";
        foreach ($fotos as $f) {
            $csv .= implode(',', [
                $f->id,
                '"'.($f->uploader_name ?? '').'"',
                '"'.($f->uploader_phone ?? '').'"',
                $f->media_type,
                $f->video_duration ?? '',
                $f->status,
                $f->id === $onScreenId ? 'Yes' : 'No',
                $f->created_at->format('Y-m-d H:i'),
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"fotos-{$event->slug}.csv\"",
        ]);
    }

    public function downloadAll(Event $event)
    {
        $fotos = $event->fotoUploads()->get();

        $zip = new \ZipArchive;
        $zipFileName = 'fotos-'.$event->slug.'-'.time().'.zip';
        $zipPath = $this->mediaStagingDirectory().DIRECTORY_SEPARATOR.$zipFileName;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($fotos as $foto) {
                $filePath = $this->stageMediaFile($foto->file_path);
                if ($filePath !== null) {
                    $zip->addFile($filePath, basename($foto->file_path));
                }
            }
            $zip->close();
        }

        $this->discardStagedMediaFiles();

        if (file_exists($zipPath)) {
            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Could not create zip file.');
    }
}
