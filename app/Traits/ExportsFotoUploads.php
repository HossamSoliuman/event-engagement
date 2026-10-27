<?php

namespace App\Traits;

use App\Models\Event;
use App\Models\FotoUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The Foto Queue's CSV export and "Download All" for admins and moderators.
 * Both cover every day of the event, archived uploads included.
 */
trait ExportsFotoUploads
{
    use StagesMediaFiles;

    protected function fotoUploadsCsv(Event $event): Response
    {
        $onScreenIds = $event->onScreenFotoIds();
        $csv = fopen('php://temp', 'r+b');

        fputcsv($csv, ['ID', 'Uploader Name', 'Phone', 'Media Type', 'Duration (s)', 'Status', 'On Screen', 'Uploaded At', 'Archived At']);

        $event->allFotoUploads()->lazyById(500)->each(function (FotoUpload $foto) use ($csv, $onScreenIds): void {
            fputcsv($csv, [
                $foto->id,
                $foto->uploader_name ?? '',
                $foto->uploader_phone ?? '',
                $foto->media_type,
                $foto->video_duration ?? '',
                $foto->status,
                in_array($foto->id, $onScreenIds, true) ? 'Yes' : 'No',
                $foto->created_at->format('Y-m-d H:i'),
                $foto->archived_at?->format('Y-m-d H:i') ?? '',
            ]);
        });

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"fotos-{$event->slug}.csv\"",
        ]);
    }

    protected function fotoUploadsZip(Event $event): BinaryFileResponse|RedirectResponse
    {
        $zip = new \ZipArchive;
        $zipPath = $this->mediaStagingDirectory().DIRECTORY_SEPARATOR.'fotos-'.$event->slug.'-'.time().'.zip';

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $event->allFotoUploads()->lazyById(200)->each(function (FotoUpload $foto) use ($zip): void {
                $filePath = $this->stageMediaFile($foto->file_path);

                if ($filePath !== null) {
                    $zip->addFile($filePath, basename($foto->file_path));
                }
            });
            $zip->close();
        }

        $this->discardStagedMediaFiles();

        if (file_exists($zipPath)) {
            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Could not create zip file.');
    }
}
