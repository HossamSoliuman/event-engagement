@extends('layouts.moderator')
@section('title', 'Members')
@section('page-title', 'Members — ' . $event->name)

@section('topbar-actions')
    <a href="{{ route('moderator.membership.export', $event) }}" class="btn btn-secondary btn-sm">Export CSV</a>
@endsection

@section('content')

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-label">Total Members</div>
        <div class="stat-value c-green">{{ $totalCount }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Newsletter Opt-in</div>
        <div class="stat-value c-mod">{{ $newsletterCount }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Members</h3>
        <form method="GET" action="{{ route('moderator.membership.index', $event) }}" class="search-bar">
            <input name="search" class="form-control" style="width:220px" placeholder="Search name or email…" value="{{ $search }}">
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Team</th>
                    @foreach($memberExtraKeys as $key)<th>{{ $key }}</th>@endforeach
                    <th>Newsletter</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                <tr>
                    <td class="text-muted">{{ $member->id }}</td>
                    <td class="font-bold">{{ $member->name }}</td>
                    <td>{{ $member->email }}</td>
                    <td class="text-muted">{{ $member->phone ?? '—' }}</td>
                    <td class="text-muted">{{ $member->team_preference ?? '—' }}</td>
                    @foreach($memberExtraKeys as $key)
                    <td class="text-muted">{{ $member->extra_fields[$key] ?? '—' }}</td>
                    @endforeach
                    <td>
                        @if($member->newsletter_opt_in)
                            <span class="badge badge-approved">Yes</span>
                        @else
                            <span class="text-muted">No</span>
                        @endif
                    </td>
                    <td class="text-muted text-xs">{{ $member->created_at->format('d M, H:i') }}</td>
                    <td>
                        <form method="POST" action="{{ route('moderator.membership.destroy', [$event, $member]) }}" data-confirm="Remove this member?">
                            @csrf @method('DELETE') <button class="btn btn-ghost btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="99" class="text-muted" style="text-align:center;padding:40px">No members yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
    <div style="padding:16px 20px">{{ $members->withQueryString()->links() }}</div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.querySelectorAll('form[data-confirm]').forEach(f => {
    f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});
</script>
@endpush
