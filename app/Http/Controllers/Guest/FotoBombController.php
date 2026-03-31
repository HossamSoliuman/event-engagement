<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class FotoBombController extends Controller
{
    public function upload(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->where('module_fotobomb', true)->firstOrFail();

        $request->validate([
            'photo'          => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'uploader_name'  => 'nullable|string|max:100',
            'uploader_phone' => 'nullable|string|max:30',
        ]);

        $file      = $request->file('photo');
        $directory = "fotos/event-{$event->id}";
        $path      = $file->store($directory, 'public');
        $thumbPath = null;

        try {
            $thumb = Image::make(Storage::disk('public')->path($path))
                ->fit(500, 500)->encode('jpg', 80);
            $thumbPath = $directory . '/thumb_' . basename($path);
            Storage::disk('public')->put($thumbPath, $thumb);
        } catch (\Exception $e) {}

        $foto = FotoUpload::create([
            'event_id'         => $event->id,
            'file_path'        => $path,
            'thumbnail_path'   => $thumbPath,
            'original_filename'=> $file->getClientOriginalName(),
            'file_size'        => $file->getSize(),
            'mime_type'        => $file->getMimeType(),
            'uploader_name'    => $request->input('uploader_name'),
            'uploader_phone'   => $request->input('uploader_phone'),
            'uploader_session' => $request->cookie("eb_session_{$event->id}"),
            'status'           => 'pending',
        ]);

        ActivityLog::record('foto.uploaded', ['uploader' => $foto->uploader_name], $event->id);

        return response()->json(['success' => true, 'message' => "🎉 Photo submitted! Watch the big screen.", 'foto_id' => $foto->id]);
    }
}
