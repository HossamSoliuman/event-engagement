@extends('layouts.admin')
@section('title', 'Lottery — ' . $event->name)
@section('page-title')
    <i data-lucide="ticket" class="lucide-icon"></i> Lottery
@endsection

@section('topbar-actions')
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
    <a href="{{ route('admin.lottery.export', $event) }}" class="btn btn-ghost btn-sm"><i data-lucide="download" class="lucide-icon"></i> CSV</a>
    <a href="{{ route('admin.events.show', $event) }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="lucide-icon"></i> Event</a>
@endsection

@section('content')
    @include('admin.lottery._body')
@endsection
