@extends('layouts.moderator')
@section('title', 'Vidiwall Player — ' . $event->name)
@section('page-title', 'Foto Queue — ' . $event->name)
@php $pendingCount = $counts['pending']; @endphp

@section('topbar-actions')
    <a href="{{ route('moderator.fotos.export', $event) }}" class="btn btn-secondary btn-sm">Export CSV</a>
    <a href="{{ route('moderator.fotos.download-all', $event) }}" class="btn btn-secondary btn-sm">Download All</a>
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall</a>
@endsection

@section('content')
    @include('admin.fotos._tabs', ['active' => 'player'])
    @include('admin.vidiwall-player._body')
@endsection
