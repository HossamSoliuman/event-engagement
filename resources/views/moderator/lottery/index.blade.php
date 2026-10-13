@extends('layouts.moderator')
@section('title', 'Lottery')
@section('page-title', 'Lottery — ' . $event->name)

@section('topbar-actions')
    <a href="{{ route('moderator.lottery.export', $event) }}" class="btn btn-secondary btn-sm">Export CSV</a>
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall</a>
@endsection

@section('content')
    @include('admin.lottery._body')
@endsection
