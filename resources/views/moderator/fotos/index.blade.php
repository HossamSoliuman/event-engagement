@extends('layouts.moderator')
@section('title', 'Foto Queue')
@section('page-title', 'Foto Queue — ' . $event->name)
@php $pendingCount = $counts['pending']; @endphp

@section('topbar-actions')
    <a href="{{ route('moderator.fotos.export', $event) }}" class="btn btn-secondary btn-sm">Export CSV</a>
    <a href="{{ route('moderator.fotos.download-all', $event) }}" class="btn btn-secondary btn-sm">Download All</a>
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall</a>
@endsection

@section('content')
    @include('admin.fotos._queue', [
        'routePrefix' => 'moderator.',
        'urls' => [
            'list' => route('moderator.fotos.index', $event),
            'status' => route('moderator.fotos.status', $event),
            'approve' => route('moderator.fotos.approve', [$event, '__ID__']),
            'reject' => route('moderator.fotos.reject', [$event, '__ID__']),
            'delete' => route('moderator.fotos.destroy', [$event, '__ID__']),
        ],
    ])
@endsection
