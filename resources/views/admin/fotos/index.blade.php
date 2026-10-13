@extends('layouts.admin')
@section('title','Foto Queue — '.$event->name)
@section('page-title','Foto Queue')

@section('topbar-actions')
    <span id="liveFotoBadge" class="badge badge-on-screen" @if(!$onScreen) hidden @endif>Live: {{ Str::limit($onScreen?->uploader_name ?? 'Guest',16) }}</span>
    <span id="queueBadge" class="badge" title="Approved items waiting to play on the vidiwall" @if(!$queued) hidden @endif>Queue: {{ $queued }}</span>
    <a href="{{ route('vidiwall.show',$event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
    <a href="{{ route('admin.fotos.export',$event) }}" class="btn btn-ghost btn-sm">CSV</a>
    <a href="{{ route('admin.fotos.download-all',$event) }}" class="btn btn-ghost btn-sm">Download All</a>
@endsection

@section('content')
    @include('admin.fotos._queue', [
        'routePrefix' => 'admin.',
        'urls' => [
            'list' => route('admin.fotos.index', $event),
            'status' => route('admin.fotos.status', $event),
            'approve' => route('admin.fotos.approve', ['foto' => '__ID__']),
            'reject' => route('admin.fotos.reject', ['foto' => '__ID__']),
            'delete' => route('admin.fotos.destroy', ['foto' => '__ID__']),
        ],
    ])
@endsection
