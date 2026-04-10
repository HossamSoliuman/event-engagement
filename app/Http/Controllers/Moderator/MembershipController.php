<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Membership;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function index(Event $event, Request $request)
    {
        $search  = $request->get('search');
        $members = $event->memberships()
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%"))
            ->latest()
            ->paginate(30);

        $totalCount      = $event->memberships()->count();
        $newsletterCount = $event->memberships()->where('newsletter_opt_in', true)->count();

        $memberExtraKeys = [];
        foreach ($members as $member) {
            if (!empty($member->extra_fields) && is_array($member->extra_fields)) {
                $memberExtraKeys = array_keys($member->extra_fields);
                break;
            }
        }

        return view('moderator.membership.index', compact('event', 'members', 'totalCount', 'newsletterCount', 'search', 'memberExtraKeys'));
    }

    public function destroy(Event $event, Membership $member)
    {
        $member->delete();
        return back()->with('success', 'Member removed.');
    }

    public function export(Event $event)
    {
        $members = $event->memberships()->get();
        $csv     = "ID,Name,Email,Phone,Team,Newsletter,Membership Number,Joined At\n";
        foreach ($members as $m) {
            $csv .= implode(',', [
                $m->id,
                '"' . $m->name . '"',
                '"' . $m->email . '"',
                '"' . ($m->phone ?? '') . '"',
                '"' . ($m->team_preference ?? '') . '"',
                $m->newsletter_opt_in ? 'Yes' : 'No',
                '"' . ($m->membership_number ?? '') . '"',
                $m->created_at->format('Y-m-d H:i'),
            ]) . "\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"members-{$event->slug}.csv\"",
        ]);
    }
}
