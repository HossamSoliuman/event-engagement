<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::withCount(['fotoUploads', 'lotteryEntries', 'votes', 'memberships'])->latest()->paginate(15);
        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        return view('admin.events.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateEvent($request);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));
        $data = $this->handleUploads($request, $data);
        $data['voting_options'] = $this->parseVotingOptions($request);

        $event = Event::create($data);
        try {
            $event->generateQrCode();
        } catch (\Exception $e) {
        }

        ActivityLog::record('event.created', ['name' => $event->name], $event->id);
        return redirect()->route('admin.events.show', $event)->with('success', 'Event created and QR generated!');
    }

    public function show(Event $event)
    {
        $event->loadCount([
            'fotoUploads',
            'lotteryEntries',
            'votes',
            'memberships',
            'fotoUploads as pending_count'  => fn($q) => $q->where('status', 'pending'),
            'fotoUploads as approved_count' => fn($q) => $q->where('status', 'approved'),
        ]);
        $tallies   = $event->getVoteTallies();
        $onScreen  = $event->getOnScreenFotos()->first();
        $recentLog = $event->activityLog()->with('user')->latest()->limit(10)->get();
        return view('admin.events.show', compact('event', 'tallies', 'onScreen', 'recentLog'));
    }

    public function edit(Event $event)
    {
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validateEvent($request, $event);
        $data['is_active'] = $request->boolean('is_active');
        $data = $this->handleUploads($request, $data);
        $data['voting_options'] = $this->parseVotingOptions($request);

        $event->update($data);
        ActivityLog::record('event.updated', ['name' => $event->name], $event->id);
        return redirect()->route('admin.events.show', $event)->with('success', 'Event updated!');
    }

    public function destroy(Event $event)
    {
        if ($event->qr_code_path) {
            Storage::disk('public')->delete($event->qr_code_path);
        }

        if ($event->logo_path) {
            Storage::disk('public')->delete($event->logo_path);
        }

        if ($event->sponsor_logo_path) {
            Storage::disk('public')->delete($event->sponsor_logo_path);
        }

        $event->fotoUploads()->delete();
        $event->lotteryEntries()->delete();
        $event->votes()->delete();
        $event->memberships()->delete();
        $event->sessions()->delete();
        $event->activityLog()->delete();

        $name = $event->name;

        $event->forceDelete();

        ActivityLog::record('event.deleted', ['name' => $name]);

        return redirect()->route('admin.events.index')->with('success', 'Event deleted.');
    }

    public function generateQr(Event $event)
    {
        $event->generateQrCode();
        ActivityLog::record('event.qr_generated', [], $event->id);
        return back()->with('success', 'QR code regenerated!');
    }

    public function toggleModule(Request $request, Event $event)
    {
        $module = $request->validate(['module' => 'required|in:fotobomb,lottery,voting,membership'])['module'];
        $field  = "module_{$module}";
        $event->update([$field => !$event->$field]);
        ActivityLog::record("event.module_toggled", ['module' => $module, 'enabled' => $event->fresh()->$field], $event->id);
        return response()->json(['enabled' => $event->fresh()->$field]);
    }

    public function duplicate(Event $event)
    {
        $new = $event->replicate();
        $new->name = $event->name . ' (Copy)';
        $new->slug = Str::slug($event->name) . '-' . Str::lower(Str::random(4));
        $new->is_active = false;
        $new->qr_code_path = null;
        $new->lottery_drawn = false;
        $new->lottery_winner_id = null;
        $new->voting_closed = false;
        $new->save();
        try {
            $new->generateQrCode();
        } catch (\Exception $e) {
        }
        return redirect()->route('admin.events.edit', $new)->with('success', 'Event duplicated!');
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function validateEvent(Request $request, ?Event $event = null): array
    {
        return $request->validate([
            'name'                        => 'required|string|max:255',
            'subtitle'                    => 'nullable|string|max:255',
            'description'                 => 'nullable|string',
            'primary_color'               => 'nullable|string|max:7',
            'secondary_color'             => 'nullable|string|max:7',
            'accent_color'                => 'nullable|string|max:7',
            'fotobomb_title'              => 'nullable|string|max:100',
            'lottery_title'               => 'nullable|string|max:100',
            'voting_title'                => 'nullable|string|max:100',
            'membership_title'            => 'nullable|string|max:100',
            'fotobomb_desc'               => 'nullable|string|max:255',
            'lottery_desc'                => 'nullable|string|max:255',
            'voting_desc'                 => 'nullable|string|max:255',
            'membership_desc'             => 'nullable|string|max:255',
            'vidiwall_overlay_text'       => 'nullable|string|max:255',
            'vidiwall_slideshow_interval' => 'nullable|integer|min:3|max:60',
            'starts_at'                   => 'nullable|date',
            'ends_at'                     => 'nullable|date',
            'logo'                        => 'nullable|image|max:2048',
            'sponsor_logo'                => 'nullable|image|max:2048',
        ]);
    }

    private function handleUploads(Request $request, array $data): array
    {
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        if ($request->hasFile('sponsor_logo')) {
            $data['sponsor_logo_path'] = $request->file('sponsor_logo')->store('logos', 'public');
        }
        $data['vidiwall_show_uploader']  = $request->boolean('vidiwall_show_uploader');
        $data['vidiwall_slideshow_mode'] = $request->boolean('vidiwall_slideshow_mode');
        return $data;
    }

    private function parseVotingOptions(Request $request): string
    {
        $names     = $request->input('candidate_names', []);
        $positions = $request->input('candidate_positions', []);
        $options   = [];
        foreach ($names as $i => $name) {
            if (empty(trim($name))) continue;
            $options[] = [
                'name'     => trim($name),
                'slug'     => Str::slug($name),
                'position' => $positions[$i] ?? null,
                'image'    => null,
            ];
        }
        return json_encode($options ?: []);
    }
}
