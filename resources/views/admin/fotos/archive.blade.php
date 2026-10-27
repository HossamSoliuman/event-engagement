@extends('layouts.admin')
@section('title', 'Archived Fotos — ' . $event->name)
@section('page-title', 'Foto Queue')

@section('topbar-actions')
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
@endsection

@section('content')
    @include('admin.fotos._archive', ['routePrefix' => 'admin.'])
@endsection
