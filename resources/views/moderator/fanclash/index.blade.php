@extends('layouts.moderator')
@section('title', 'Fan Clash — ' . $event->name)
@section('page-title')
    <i data-lucide="swords" class="lucide-icon"></i> Fan Clash
@endsection

@section('topbar-actions')
    <a href="{{ route('moderator.fanclash.export', $event) }}" class="btn btn-ghost btn-sm"><i data-lucide="download" class="lucide-icon"></i> CSV</a>
    <a href="{{ route('moderator.dashboard', $event) }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="lucide-icon"></i> Dashboard</a>
@endsection

@section('content')

@if ($activeRound)
    <div class="card mb-3" style="border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.05)">
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <div style="font-weight:700;font-size:15px;color:#ef4444;display:flex;align-items:center;gap:8px">
                    Round #{{ $activeRound->id }} — Live
                    @if ($activeRound->category)
                        <span class="text-muted text-xs" style="font-weight:600">· {{ $activeRound->category }}</span>
                    @endif
                </div>
                <div class="text-sm" style="margin-top:6px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <span style="font-weight:700;color:{{ $activeRound->side_a_color }}">{{ $activeRound->side_a_name }}</span>
                    <span class="text-muted">{{ $activeRound->side_a_taps }} vs {{ $activeRound->side_b_taps }}</span>
                    <span style="font-weight:700;color:{{ $activeRound->side_b_color }}">{{ $activeRound->side_b_name }}</span>
                </div>
                <div class="text-muted text-xs" style="margin-top:4px">{{ $activeRound->duration_seconds }}s round · Started {{ $activeRound->started_at?->diffForHumans() }}</div>
            </div>
            <form method="POST" action="{{ route('moderator.fanclash.end', $event) }}">
                @csrf
                <button class="btn btn-danger btn-sm" onclick="return confirm('End the active round now?')">End Round</button>
            </form>
        </div>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<div class="grid-2" style="gap:16px;align-items:start">

    <div>
        <div class="card mb-3">
            <div class="card-header">
                <h3><i data-lucide="users" class="lucide-icon"></i> Matchup Pool ({{ $matchups->count() }})</h3>
            </div>

            @forelse ($matchups as $m)
                <div style="border-bottom:1px solid var(--border);padding:14px 18px" id="matchup-row-{{ $m->id }}">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                        <div style="flex:1">
                            @if ($m->category)
                                <div class="text-muted text-xs" style="letter-spacing:1.5px;text-transform:uppercase;margin-bottom:6px">{{ $m->category }}</div>
                            @endif
                            <div style="display:flex;align-items:center;gap:10px;font-size:14px;font-weight:700">
                                <span style="display:inline-flex;align-items:center;gap:6px">
                                    <span style="width:12px;height:12px;border-radius:3px;background:{{ $m->side_a_color ?: '#ef4444' }};display:inline-block"></span>
                                    {{ $m->side_a_name }}
                                </span>
                                <span class="text-muted text-xs">vs</span>
                                <span style="display:inline-flex;align-items:center;gap:6px">
                                    <span style="width:12px;height:12px;border-radius:3px;background:{{ $m->side_b_color ?: '#3B82F6' }};display:inline-block"></span>
                                    {{ $m->side_b_name }}
                                </span>
                                @unless ($m->is_active)
                                    <span class="badge" style="margin-left:4px">Hidden</span>
                                @endunless
                            </div>
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0">
                            @if ($m->sponsor_logo_url)
                                <img src="{{ $m->sponsor_logo_url }}" alt="Sponsor" style="height:28px;width:auto;border-radius:4px;background:#fff;padding:2px">
                            @endif
                            <button class="btn btn-ghost btn-sm" onclick="fcToggleEdit({{ $m->id }})"><i data-lucide="pencil" class="lucide-icon"></i></button>
                            <form method="POST" action="{{ route('moderator.fanclash.matchups.destroy', [$event, $m]) }}" onsubmit="return confirm('Delete this matchup?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm" style="color:var(--red)"><i data-lucide="trash-2" class="lucide-icon"></i></button>
                            </form>
                        </div>
                    </div>

                    <div id="matchup-edit-{{ $m->id }}" style="display:none;margin-top:12px">
                        <form method="POST" action="{{ route('moderator.fanclash.matchups.update', [$event, $m]) }}" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <div class="form-group">
                                <label class="form-label">Category (optional)</label>
                                <input type="text" name="category" class="form-control" value="{{ $m->category }}" placeholder="e.g. Downhill">
                            </div>
                            <div class="form-row">
                                <div class="form-group mb-0">
                                    <label class="form-label">Side A name</label>
                                    <input type="text" name="side_a_name" class="form-control" value="{{ $m->side_a_name }}" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Color</label>
                                    <input type="color" name="side_a_color" class="form-control" style="height:38px;padding:2px" value="{{ $m->side_a_color ?: '#ef4444' }}">
                                </div>
                            </div>
                            <div class="form-row" style="margin-top:12px">
                                <div class="form-group mb-0">
                                    <label class="form-label">Side B name</label>
                                    <input type="text" name="side_b_name" class="form-control" value="{{ $m->side_b_name }}" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Color</label>
                                    <input type="color" name="side_b_color" class="form-control" style="height:38px;padding:2px" value="{{ $m->side_b_color ?: '#3B82F6' }}">
                                </div>
                            </div>
                            <div class="form-group" style="margin-top:12px">
                                <label class="form-label" style="display:flex;align-items:center;gap:8px;font-weight:400;cursor:pointer">
                                    <input type="checkbox" name="is_active" value="1" {{ $m->is_active ? 'checked' : '' }}> Show in the round picker
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sponsor logo (optional)</label>
                                @if ($m->sponsor_logo_url)
                                    <label style="display:block;font-weight:400;font-size:12px;cursor:pointer;margin-bottom:6px">
                                        <input type="checkbox" name="sponsor_logo_clear" value="1"> Remove current logo
                                    </label>
                                @endif
                                <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                            </div>
                            <div style="display:flex;gap:8px">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <button type="button" class="btn btn-ghost btn-sm" onclick="fcToggleEdit({{ $m->id }})">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state" style="padding:30px">
                    <div class="empty-icon"><i data-lucide="swords" class="lucide-icon"></i></div>
                    <h3>No matchups yet</h3>
                    <p>Save a matchup, or just type two names when you start a round.</p>
                </div>
            @endforelse
        </div>

        <div class="card">
            <div class="card-header"><h3>Add Matchup</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('moderator.fanclash.matchups.store', $event) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Category (optional)</label>
                        <input type="text" name="category" class="form-control" placeholder="e.g. Downhill" value="{{ old('category') }}">
                    </div>
                    <div class="form-row">
                        <div class="form-group mb-0">
                            <label class="form-label">Side A name *</label>
                            <input type="text" name="side_a_name" class="form-control" placeholder="Odermatt" value="{{ old('side_a_name') }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Color</label>
                            <input type="color" name="side_a_color" class="form-control" style="height:38px;padding:2px" value="{{ old('side_a_color', $event->primary_color ?: '#ef4444') }}">
                        </div>
                    </div>
                    <div class="form-row" style="margin-top:12px">
                        <div class="form-group mb-0">
                            <label class="form-label">Side B name *</label>
                            <input type="text" name="side_b_name" class="form-control" placeholder="Kilde" value="{{ old('side_b_name') }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Color</label>
                            <input type="color" name="side_b_color" class="form-control" style="height:38px;padding:2px" value="{{ old('side_b_color', '#3B82F6') }}">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:12px">
                        <label class="form-label">Sponsor logo (optional)</label>
                        <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                        <div class="text-muted text-xs" style="margin-top:4px">Shown on the winner screen. PNG/JPG, max 2MB.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Matchup</button>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header"><h3><i data-lucide="play-circle" class="lucide-icon"></i> Start New Round</h3></div>
            <div class="card-body">
                @if ($activeRound)
                    <div class="empty-state" style="padding:20px">
                        <div class="text-muted text-sm">End the current live round before starting a new one.</div>
                    </div>
                @else
                    <form method="POST" action="{{ route('moderator.fanclash.start', $event) }}" id="fcStartForm">
                        @csrf
                        @if ($matchups->where('is_active', true)->count())
                            <div class="form-group">
                                <label class="form-label">Saved matchup</label>
                                <select name="fan_clash_matchup_id" class="form-control" id="fcMatchupSelect" onchange="fcSyncMode()">
                                    <option value="">— Type two names below —</option>
                                    @foreach ($matchups->where('is_active', true) as $m)
                                        <option value="{{ $m->id }}">{{ $m->side_a_name }} vs {{ $m->side_b_name }}{{ $m->category ? ' · ' . $m->category : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div id="fcAdhoc">
                            <div class="form-group">
                                <label class="form-label">Category (optional)</label>
                                <input type="text" name="category" class="form-control" placeholder="e.g. Sprint">
                            </div>
                            <div class="form-row">
                                <div class="form-group mb-0">
                                    <label class="form-label">Side A</label>
                                    <input type="text" name="side_a_name" class="form-control fc-adhoc-name" placeholder="Odermatt">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Color</label>
                                    <input type="color" name="side_a_color" class="form-control" style="height:38px;padding:2px" value="{{ $event->primary_color ?: '#ef4444' }}">
                                </div>
                            </div>
                            <div class="form-row" style="margin-top:12px">
                                <div class="form-group mb-0">
                                    <label class="form-label">Side B</label>
                                    <input type="text" name="side_b_name" class="form-control fc-adhoc-name" placeholder="Kilde">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Color</label>
                                    <input type="color" name="side_b_color" class="form-control" style="height:38px;padding:2px" value="#3B82F6">
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top:16px">
                            <label class="form-label">Round length (seconds)</label>
                            <input type="number" name="duration_seconds" class="form-control" value="20" min="5" max="120" required>
                        </div>
                        <button type="submit" class="btn btn-primary" onclick="return fcValidateStart()">Start Round</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($rounds->count())
            <div class="card">
                <div class="card-header"><h3>Round History</h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Status</th>
                                <th>Matchup</th>
                                <th>Result</th>
                                <th>Fans</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rounds as $round)
                                <tr>
                                    <td class="text-muted text-xs">{{ $round->id }}</td>
                                    <td>
                                        @if ($round->status === 'active')
                                            <span class="badge badge-active">Live</span>
                                        @elseif ($round->status === 'finished')
                                            <span class="badge badge-pending">Finished</span>
                                        @else
                                            <span class="badge">Waiting</span>
                                        @endif
                                    </td>
                                    <td class="text-sm">{{ $round->side_a_name }} vs {{ $round->side_b_name }}</td>
                                    <td class="text-sm">
                                        @if ($round->status === 'finished')
                                            {{ $round->side_a_taps }}–{{ $round->side_b_taps }}
                                            <span class="text-muted text-xs">
                                                ({{ $round->winner_side === 'a' ? $round->side_a_name : ($round->winner_side === 'b' ? $round->side_b_name : 'Tie') }})
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $round->participants_count }}</td>
                                    <td style="display:flex;gap:6px">
                                        <form method="POST" action="{{ route('moderator.fanclash.rounds.reset', [$event, $round]) }}" onsubmit="return confirm('Reset this round (clears taps and participants)?')">
                                            @csrf
                                            <button class="btn btn-ghost btn-sm" style="color:var(--muted)" title="Reset round"><i data-lucide="rotate-ccw" class="lucide-icon"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

</div>

@endsection

@push('scripts')
<script>
    function fcToggleEdit(id) {
        const f = document.getElementById('matchup-edit-' + id);
        f.style.display = f.style.display === 'none' ? '' : 'none';
    }

    function fcSyncMode() {
        const sel = document.getElementById('fcMatchupSelect');
        const adhoc = document.getElementById('fcAdhoc');
        if (!sel || !adhoc) return;
        const useMatchup = sel.value !== '';
        adhoc.style.opacity = useMatchup ? '.4' : '1';
        adhoc.querySelectorAll('input').forEach(i => i.disabled = useMatchup);
    }

    function fcValidateStart() {
        const sel = document.getElementById('fcMatchupSelect');
        if (sel && sel.value !== '') return true;
        const names = [...document.querySelectorAll('.fc-adhoc-name')].map(i => i.value.trim());
        if (names.some(n => !n)) {
            alert('Pick a saved matchup, or type both contender names.');
            return false;
        }
        return true;
    }
</script>
@endpush
