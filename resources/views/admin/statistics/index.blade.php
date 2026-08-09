@extends('layouts.admin')
@section('title', 'Statistics — ' . $event->name)
@section('page-title')
    <i data-lucide="bar-chart-3" class="lucide-icon"></i> Statistics
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.statistics.report', $event) }}" target="_blank" class="btn btn-primary btn-sm">
        <i data-lucide="printer" class="lucide-icon"></i> Print Report
    </a>
    <a href="{{ route('admin.events.show', $event) }}" class="btn btn-secondary btn-sm">
        <i data-lucide="arrow-left" class="lucide-icon"></i> Event
    </a>
@endsection

@section('content')
    @include('admin.statistics._body')
@endsection
