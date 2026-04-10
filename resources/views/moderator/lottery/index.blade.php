@extends('layouts.moderator')
@section('title', 'Lottery')
@section('page-title', 'Lottery — ' . $event->name)

@section('topbar-actions')
    <a href="{{ route('moderator.lottery.export', $event) }}" class="btn btn-secondary btn-sm">Export CSV</a>
@endsection

@section('content')

@if($winner)
<div class="winner-card mb-3">
    <div style="font-size:48px;margin-bottom:12px">★</div>
    <h2>Winner: {{ $winner->name }}</h2>
    <p>{{ $winner->phone }} @if($winner->email)· {{ $winner->email }}@endif</p>
    <p class="text-xs text-muted" style="margin-top:8px">Drawn {{ $winner->won_at?->diffForHumans() }}</p>
    <form method="POST" action="{{ route('moderator.lottery.reset', $event) }}" style="margin-top:16px" data-confirm="Reset the lottery? The winner will be cleared.">
        @csrf <button class="btn btn-outline btn-sm">Reset Draw</button>
    </form>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3>Lottery Entries <span class="text-muted" style="font-weight:400;font-size:13px">{{ $totalCount }} total</span></h3>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <form method="GET" action="{{ route('moderator.lottery.index', $event) }}" class="search-bar">
                <input name="search" class="form-control" style="width:200px" placeholder="Search name or phone…" value="{{ $search }}">
            </form>
            @if(!$event->lottery_drawn)
            <form method="POST" action="{{ route('moderator.lottery.draw', $event) }}" data-confirm="Draw the lottery winner now?">
                @csrf <button class="btn btn-gold">Draw Winner</button>
            </form>
            @else
            <form method="POST" action="{{ route('moderator.lottery.reset', $event) }}" data-confirm="Reset the lottery?">
                @csrf <button class="btn btn-outline">Reset</button>
            </form>
            @endif
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    @foreach($lotteryExtraKeys as $key)<th>{{ $key }}</th>@endforeach
                    <th>Status</th>
                    <th>Entered</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td class="text-muted">{{ $entry->id }}</td>
                    <td class="font-bold">
                        {{ $entry->name }}
                        @if($entry->is_winner)<span class="badge badge-winner" style="margin-left:6px">Winner</span>@endif
                    </td>
                    <td>{{ $entry->phone }}</td>
                    <td class="text-muted">{{ $entry->email ?? '—' }}</td>
                    @foreach($lotteryExtraKeys as $key)
                    <td class="text-muted">{{ $entry->extra_fields[$key] ?? '—' }}</td>
                    @endforeach
                    <td>
                        @if($entry->is_winner)
                            <span class="badge badge-winner">Winner</span>
                        @else
                            <span class="badge" style="background:rgba(255,255,255,.05);color:var(--muted)">Entered</span>
                        @endif
                    </td>
                    <td class="text-muted text-xs">{{ $entry->created_at->format('d M, H:i') }}</td>
                    <td>
                        <form method="POST" action="{{ route('moderator.lottery.destroy', [$event, $entry]) }}" data-confirm="Remove this entry?">
                            @csrf @method('DELETE') <button class="btn btn-ghost btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="99" class="text-muted" style="text-align:center;padding:40px">No entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
    <div style="padding:16px 20px">{{ $entries->withQueryString()->links() }}</div>
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
