<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::withCount(['fotoUploads', 'lotteryEntries', 'votes', 'memberships'])
            ->latest()
            ->paginate(15);

        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        return view('admin.events.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'subtitle'          => 'nullable|string|max:255',
            'description'       => 'nullable|string',
            'primary_color'     => 'nullable|string|size:7',
            'secondary_color'   => 'nullable|string|size:7',
            'accent_color'      => 'nullable|string|size:7',
            'logo'              => 'nullable|image|max:2048',
            'sponsor_logo'      => 'nullable|image|max:2048',
        ]);

        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        if ($request->hasFile('sponsor_logo')) {
            $data['sponsor_logo_path'] = $request->file('sponsor_logo')->store('logos', 'public');
        }

        $event = Event::create($data);
        $event->generateQrCode();

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Event created and QR code generated!');
    }

    public function show(Event $event)
    {
        $event->loadCount(['fotoUploads', 'lotteryEntries', 'votes', 'memberships']);
        $pending  = $event->fotoUploads()->where('status', 'pending')->count();
        $approved = $event->fotoUploads()->where('status', 'approved')->count();

        return view('admin.events.show', compact('event', 'pending', 'approved'));
    }

    public function edit(Event $event)
    {
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'subtitle'          => 'nullable|string|max:255',
            'description'       => 'nullable|string',
            'primary_color'     => 'nullable|string|size:7',
            'secondary_color'   => 'nullable|string|size:7',
            'accent_color'      => 'nullable|string|size:7',
            'fotobomb_title'    => 'nullable|string|max:100',
            'lottery_title'     => 'nullable|string|max:100',
            'voting_title'      => 'nullable|string|max:100',
            'membership_title'  => 'nullable|string|max:100',
            'is_active'         => 'boolean',
            'logo'              => 'nullable|image|max:2048',
            'sponsor_logo'      => 'nullable|image|max:2048',
        ]);


        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        if ($request->hasFile('sponsor_logo')) {
            $data['sponsor_logo_path'] = $request->file('sponsor_logo')->store('logos', 'public');
        }

        $event->update($data);

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Event updated successfully.');
    }


    public function destroy(Event $event)
    {
        DB::transaction(function () use ($event) {

            if ($event->logo_path) {
                Storage::disk('public')->delete($event->logo_path);
            }

            if ($event->sponsor_logo_path) {
                Storage::disk('public')->delete($event->sponsor_logo_path);
            }

            if ($event->qr_code_path) {
                Storage::disk('public')->delete($event->qr_code_path);
            }

            foreach ($event->fotoUploads as $upload) {
                if ($upload->photo_path) {
                    Storage::disk('public')->delete($upload->photo_path);
                }
            }

            $event->fotoUploads()->delete();
            $event->lotteryEntries()->delete();
            $event->votes()->delete();
            $event->memberships()->delete();

            $event->delete();
        });

        return redirect()->route('admin.events.index')
            ->with('success', 'Event and all related data deleted.');
    }

    public function generateQr(Event $event)
    {
        $event->generateQrCode();
        return back()->with('success', 'QR code regenerated!');
    }

    public function toggleModule(Request $request, Event $event)
    {
        $module = $request->validate(['module' => 'required|in:fotobomb,lottery,voting,membership'])['module'];
        $field  = "module_{$module}";
        $event->update([$field => !$event->$field]);

        return response()->json(['enabled' => $event->fresh()->$field]);
    }
}
