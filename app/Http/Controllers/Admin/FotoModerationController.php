<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FotoUpload;
use Illuminate\Http\Request;

class FotoModerationController extends Controller
{
    public function index(Event $event, Request $request)
    {
        $status  = $request->get('status', 'pending');
        $fotos   = $event->fotoUploads()
            ->where('status', $status)
            ->latest()
            ->paginate(20);

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
        return back()->with('success', 'Photo approved!');
    }

    public function reject(FotoUpload $foto)
    {
        $foto->reject();
        return back()->with('success', 'Photo rejected.');
    }

    public function pushToScreen(FotoUpload $foto)
    {
        if (!$foto->isApproved()) {
            $foto->approve(auth()->id());
        }
        $foto->pushToScreen();

        // Broadcast event for real-time vidiwall update
        // event(new \App\Events\FotoPushedToScreen($foto));  // V2

        return back()->with('success', '🎬 Photo is now live on the vidiwall!');
    }

    public function removeFromScreen(FotoUpload $foto)
    {
        $foto->removeFromScreen();
        return back()->with('success', 'Photo removed from screen.');
    }

    public function destroy(FotoUpload $foto)
    {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($foto->file_path);
        if ($foto->thumbnail_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($foto->thumbnail_path);
        }
        $foto->delete();
        return back()->with('success', 'Photo deleted.');
    }
}
