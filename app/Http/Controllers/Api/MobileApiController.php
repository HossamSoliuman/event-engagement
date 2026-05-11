<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileApiController extends Controller
{
    // ── Auth ──────────────────────────────────────────────────────────────────

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password) || !$user->is_active) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('mobile-admin')->plainTextToken;
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'token' => $token,
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'avatar_url' => $user->avatar_url],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $activeEvent = Event::where('is_active', true)->first();
        return response()->json([
            'active_event'  => $activeEvent ? [
                'id'            => $activeEvent->id,
                'name'          => $activeEvent->name,
                'slug'          => $activeEvent->slug,
                'pending_fotos' => $activeEvent->getPendingFotosCount(),
                'modules'       => [
                    'fotobomb'   => $activeEvent->module_fotobomb,
                    'lottery'    => $activeEvent->module_lottery,
                    'voting'     => $activeEvent->module_voting,
                    'membership' => $activeEvent->module_membership,
                ],
            ] : null,
            'total_events'  => Event::count(),
            'pending_fotos' => FotoUpload::where('status','pending')->count(),
        ]);
    }

    // ── Fotos ─────────────────────────────────────────────────────────────────

    public function pendingFotos(Event $event)
    {
        $fotos = $event->fotoUploads()
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data'  => $fotos->map(fn($f) => [
                'id'             => $f->id,
                'media_type'     => $f->media_type,
                'thumbnail_url'  => $f->thumbnail_url,
                'file_url'       => $f->file_url,
                'video_url'      => $f->video_url,
                'video_duration' => $f->video_duration,
                'uploader_name'  => $f->uploader_name,
                'uploader_phone' => $f->uploader_phone,
                'uploaded_at'    => $f->created_at->diffForHumans(),
            ]),
            'total' => $fotos->total(),
            'pages' => $fotos->lastPage(),
        ]);
    }

    public function approveFoto(Request $request, FotoUpload $foto)
    {
        $foto->approve($request->user()->id);
        ActivityLog::record('foto.approved', ['foto_id' => $foto->id], $foto->event_id);
        return response()->json(['success' => true, 'message' => 'Approved.']);
    }

    public function rejectFoto(Request $request, FotoUpload $foto)
    {
        $foto->reject($request->input('note'));
        return response()->json(['success' => true, 'message' => 'Rejected.']);
    }

    public function pushToScreen(Request $request, FotoUpload $foto)
    {
        if (!$foto->isApproved()) $foto->approve($request->user()->id);
        $foto->pushToScreen();
        ActivityLog::record('foto.pushed_to_screen', ['foto_id' => $foto->id], $foto->event_id);
        return response()->json(['success' => true, 'message' => '📺 Live on vidiwall!']);
    }

    // ── Event Stats ───────────────────────────────────────────────────────────

    public function eventStats(Event $event)
    {
        return response()->json([
            'name'            => $event->name,
            'foto_uploads'    => $event->fotoUploads()->count(),
            'pending_fotos'   => $event->getPendingFotosCount(),
            'approved_fotos'  => $event->fotoUploads()->where('status','approved')->count(),
            'lottery_entries' => $event->lotteryEntries()->count(),
            'total_votes'     => $event->votes()->count(),
            'memberships'     => $event->memberships()->count(),
            'vote_tallies'    => $event->getVoteTallies(),
            'lottery_drawn'   => $event->lottery_drawn,
            'modules'         => [
                'fotobomb'   => $event->module_fotobomb,
                'lottery'    => $event->module_lottery,
                'voting'     => $event->module_voting,
                'membership' => $event->module_membership,
            ],
        ]);
    }

    public function toggleModule(Request $request, Event $event)
    {
        $module = $request->validate(['module' => 'required|in:fotobomb,lottery,voting,membership'])['module'];
        $field  = "module_{$module}";
        $event->update([$field => !$event->$field]);
        return response()->json(['enabled' => $event->fresh()->$field]);
    }
}
