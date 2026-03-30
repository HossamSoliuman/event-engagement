<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FotoUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class FotoBombController extends Controller
{
    public function upload(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)
            ->where('is_active', true)
            ->where('module_fotobomb', true)
            ->firstOrFail();

        $request->validate([
            'photo'          => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'uploader_name'  => 'nullable|string|max:100',
            'uploader_phone' => 'nullable|string|max:30',
        ]);

        $file      = $request->file('photo');
        $directory = "fotos/event-{$event->id}";

        // Store original
        $path = $file->store($directory, 'public');

        // Create thumbnail using Intervention Image
        $thumbPath = null;
        try {
            $thumb = Image::make(Storage::disk('public')->path($path))
                ->fit(400, 400)
                ->encode('jpg', 80);

            $thumbPath = $directory . '/thumb_' . basename($path);
            Storage::disk('public')->put($thumbPath, $thumb);
        } catch (\Exception $e) {
            // thumbnail generation failed — not critical
        }

        $foto = FotoUpload::create([
            'event_id'       => $event->id,
            'file_path'      => $path,
            'thumbnail_path' => $thumbPath,
            'uploader_name'  => $request->input('uploader_name'),
            'uploader_phone' => $request->input('uploader_phone'),
            'status'         => 'pending',
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Your photo has been submitted! 🎉',
            'foto_id'   => $foto->id,
        ]);
    }
}
