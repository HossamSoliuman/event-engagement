@extends('layouts.admin')
@section('title', 'Members — ' . $event->name)
@section('page-title', '<i data-lucide="star" class="lucide-icon"></i> Members')

@section('topbar-actions')
    <a href="{{ route('admin.membership.export', $event) }}" class="btn btn-ghost btn-sm">⬇ CSV</a>
    <a href="{{ route('admin.events.show', $event) }}" class="btn btn-secondary btn-sm">← Event</a>
@endsection

@section('content')

    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:20px">
        <div class="stat-card">
            <div class="stat-label">Total Members</div>
            <div class="stat-value c-green">{{ $totalCount }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Newsletter Opt-In</div>
            <div class="stat-value c-blue">{{ $newsletterCount }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Opt-In Rate</div>
            <div class="stat-value">{{ $totalCount > 0 ? round(($newsletterCount / $totalCount) * 100) : 0 }}%</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>All Members</h3>
            <form method="GET" action="{{ route('admin.membership.index', $event) }}" style="display:flex;gap:8px">
                <div class="search-bar">
                    <input name="search" class="form-control" placeholder="Name or email…" value="{{ $search }}"
                        style="width:220px">
                </div>
                <button class="btn btn-secondary btn-sm">Search</button>
                @if ($search)
                    <a href="{{ route('admin.membership.index', $event) }}" class="btn btn-ghost btn-sm"><i data-lucide="x" class="lucide-icon"></i></a>
                @endif
            </form>
        </div>
       

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Team</th>
                        @foreach ($memberExtraKeys as $key)
                            <th>{{ $key }}</th>
                        @endforeach
                        <th>Newsletter</th>
                        <th>Member #</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td><span class="font-bold">{{ $member->name }}</span></td>
                            <td>{{ $member->email }}</td>
                            <td class="text-muted">{{ $member->phone ?? '—' }}</td>
                            <td class="text-muted">{{ $member->team_preference ?? '—' }}</td>
                            @foreach ($memberExtraKeys as $key)
                                <td class="text-muted">{{ $member->extra_fields[$key] ?? '—' }}</td>
                            @endforeach
                            <td>
                                @if ($member->newsletter_opt_in)
                                    <span class="badge badge-approved"><i data-lucide="check" class="lucide-icon"></i> Yes</span>
                                @else
                                    <span class="badge badge-inactive">No</span>
                                @endif
                            </td>
                            <td><code>{{ $member->membership_number ?? '—' }}</code></td>
                            <td class="text-muted text-xs">{{ $member->created_at->format('M d · H:i') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.membership.destroy', $member) }}"
                                    onsubmit="return confirm('Remove this member?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm" style="color:var(--red)" title="Remove"><i data-lucide="trash-2" class="lucide-icon"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 8 + count($memberExtraKeys) }}">
                                <div class="empty-state" style="padding:30px">No members found.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($members->hasPages())
            <div style="padding:12px 18px">{{ $members->appends(['search' => $search])->links() }}</div>
        @endif
    </div>
@endsection
