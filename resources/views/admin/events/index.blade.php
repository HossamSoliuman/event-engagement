@extends('layouts.admin')
@section('page-title', 'Events')

@section('topbar-actions')
    <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">+ New Event</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 style="font-size:15px;">All Events ({{ $events->total() }})</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Slug</th>
                        <th>Uploads</th>
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
                                <strong>{{ $event->name }}</strong>
                                <div class="text-muted text-sm">{{ $event->subtitle }}</div>
                            </td>
                            <td><code style="color:var(--eb-muted);font-size:12px;">/e/{{ $event->slug }}</code></td>
                            <td>{{ $event->foto_uploads_count }}</td>
                            <td>{{ $event->lottery_entries_count + $event->memberships_count }}</td>
                            <td>
                                <span class="badge {{ $event->is_active ? 'badge-approved' : 'badge-rejected' }}">
                                    {{ $event->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-muted text-sm">{{ $event->created_at->format('M d, Y') }}</td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="{{ route('admin.events.show', $event) }}"
                                        class="btn btn-secondary btn-sm">Manage</a>
                                    <a href="{{ route('admin.fotos.index', $event) }}"
                                        class="btn btn-outline btn-sm">Fotos</a>
                                    <a href="{{ route('admin.events.edit', $event) }}"
                                        class="btn btn-outline btn-sm">Edit</a>

                                    <form action="{{ route('admin.events.destroy', $event) }}" method="POST"
                                        onsubmit="return confirm('Delete this event and ALL its data? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:60px;color:var(--eb-muted);">
                                No events yet. <a href="{{ route('admin.events.create') }}"
                                    style="color:var(--eb-red);">Create your first →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div style="padding:16px 22px;">{{ $events->links() }}</div>
        @endif
    </div>
@endsection
