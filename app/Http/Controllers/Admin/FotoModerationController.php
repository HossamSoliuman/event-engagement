<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FotoModerationController extends Controller
{
    public function index(Event $event, Request $request)
    {
        $status = $request->get('status', 'pending');

        $fotos = $event->fotoUploads()
            ->where('status', $status)
            ->latest()
            ->paginate(24);

        $counts = [
            'pending'  => $event->fotoUploads()->where('status', 'pending')->count(),
            'approved' => $event->fotoUploads()->where('status', 'approved')->count(),
            'rejected' => $event->fotoUploads()->where('status', 'rejected')->count(),
        ];

        $onScreen = $event->fotoUploads()
            ->where('on_screen', true)
            ->where('status', 'approved')
            ->first();

        return view('admin.fotos.index', compact('event', 'fotos', 'status', 'counts', 'onScreen'));
    }

    public function approve(FotoUpload $foto)
    {
        $foto->approve(auth()->id());
        ActivityLog::record('foto.approved', ['foto_id' => $foto->id, 'uploader' => $foto->uploader_name], $foto->event_id);
        return back()->with('success', '✅ Photo approved!');
    }

    public function reject(Request $request, FotoUpload $foto)
    {
        $foto->reject($request->input('note'));
        ActivityLog::record('foto.rejected', ['foto_id' => $foto->id], $foto->event_id);
        return back()->with('success', 'Photo rejected.');
    }

    public function pushToScreen(FotoUpload $foto)
    {
        if (!$foto->isApproved()) {
            $foto->approve(auth()->id());
        }
        $foto->pushToScreen();
        ActivityLog::record('foto.pushed_to_screen', ['foto_id' => $foto->id, 'uploader' => $foto->uploader_name], $foto->event_id);
        return back()->with('success', '📺 Photo is now LIVE on the vidiwall!');
    }

    public function removeFromScreen(FotoUpload $foto)
    {
        $foto->removeFromScreen();
        ActivityLog::record('foto.removed_from_screen', ['foto_id' => $foto->id], $foto->event_id);
        return back()->with('success', 'Removed from screen.');
    }

    public function destroy(FotoUpload $foto)
    {
        Storage::disk('public')->delete($foto->file_path);
        if ($foto->thumbnail_path) Storage::disk('public')->delete($foto->thumbnail_path);
        $foto->delete();
        ActivityLog::record('foto.deleted', ['foto_id' => $foto->id], $foto->event_id);
        return back()->with('success', 'Photo deleted.');
    }

    public function export(Event $event)
    {
        $fotos = $event->fotoUploads()->get();
        $csv   = "ID,Uploader Name,Phone,Status,On Screen,Uploaded At\n";
        foreach ($fotos as $f) {
            $csv .= implode(',', [
                $f->id, '"'.($f->uploader_name??'').'""',
                '"'.($f->uploader_phone??'').'""',
                $f->status, $f->on_screen ? 'Yes' : 'No',
                $f->created_at->format('Y-m-d H:i'),
            ]) . "\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"fotos-{$event->slug}.csv\"",
        ]);
    }

    public function downloadAll(Event $event)
    {
        $fotos = $event->fotoUploads()->get();
        
        $zip = new \ZipArchive();
        $zipFileName = 'fotos-' . $event->slug . '-' . time() . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($fotos as $foto) {
                $filePath = storage_path('app/public/' . $foto->file_path);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, basename($foto->file_path));
                }
            }
            $zip->close();
        }

        if (file_exists($zipPath)) {
            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Could not create zip file.');
    }
}
