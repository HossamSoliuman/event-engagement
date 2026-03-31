@extends('layouts.admin')
@section('title', 'Events')
@section('page-title', 'Events')
@section('topbar-actions')
    <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">+ New Event</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>All Events ({{ $events->total() }})</h3>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Modules</th>
                        <th>Fotos</th>
                        <th>Entries</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>
                                <div class="font-bold" style="font-size:13px">{{ $event->name }}</div>
                                <code>/e/{{ $event->slug }}</code>
                            </td>
                            <td>
                                <div style="display:flex;gap:3px;flex-wrap:wrap">
                                    @foreach (['fotobomb' => '📷', 'lottery' => '🎰', 'voting' => '🏆', 'membership' => '⭐'] as $mod => $ico)
                                        <span title="{{ ucfirst($mod) }}"
                                            style="font-size:14px;opacity:{{ $event->{'module_' . $mod} ? 1 : 0.2 }}">{{ $ico }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>{{ $event->foto_uploads_count }}</td>
                            <td>{{ $event->lottery_entries_count + $event->memberships_count }}</td>
                            <td><span
                                    class="badge {{ $event->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $event->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-muted text-xs">{{ $event->created_at->format('M d, Y') }}</td>
                            <td>
                                <div style="display:flex;gap:5px">
                                    <a href="{{ route('admin.events.show', $event) }}"
                                        class="btn btn-secondary btn-sm">Manage</a>
                                    <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-ghost btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('admin.events.duplicate', $event) }}">
                                        @csrf <button class="btn btn-ghost btn-sm" title="Duplicate">⧉</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                                        onsubmit="return confirm('Delete this event?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon">◉</div>
                                    <h3>No events yet</h3>
                                    <p><a href="{{ route('admin.events.create') }}" style="color:var(--red)">Create your
                                            first event →</a></p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div style="padding:14px 18px">{{ $events->links() }}</div>
        @endif
    </div>
@endsection
