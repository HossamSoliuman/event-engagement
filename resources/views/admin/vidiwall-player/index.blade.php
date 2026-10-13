@extends('layouts.admin')
@section('title', 'Vidiwall Player — ' . $event->name)
@section('page-title', 'Foto Queue')

@section('topbar-actions')
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
    <a href="{{ route('admin.fotos.export', $event) }}" class="btn btn-ghost btn-sm">CSV</a>
    <a href="{{ route('admin.fotos.download-all', $event) }}" class="btn btn-ghost btn-sm">Download All</a>
@endsection

@section('content')
    @include('admin.fotos._tabs', ['active' => 'player'])
    @include('admin.vidiwall-player._body')
@endsection
