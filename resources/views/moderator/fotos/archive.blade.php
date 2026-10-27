@extends('layouts.moderator')
@section('title', 'Archived Fotos')
@section('page-title', 'Foto Queue — ' . $event->name)
@php $pendingCount = $counts['pending']; @endphp

@section('topbar-actions')
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall</a>
@endsection

@section('content')
    @include('admin.fotos._archive', ['routePrefix' => 'moderator.'])
@endsection
