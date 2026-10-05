@extends('layouts.admin')
@section('title', 'Fan Survey — ' . $event->name)
@section('page-title')
    <i data-lucide="clipboard-list" class="lucide-icon"></i> Fan Survey
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.survey.export', $event) }}" class="btn btn-ghost btn-sm"><i data-lucide="download" class="lucide-icon"></i> CSV</a>
    <a href="{{ route('admin.events.show', $event) }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="lucide-icon"></i> Event</a>
@endsection

@section('content')
    @include('admin.survey._body')
@endsection
