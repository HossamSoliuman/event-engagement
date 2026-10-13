@extends('layouts.moderator')
@section('title', 'Voting')
@section('page-title', 'Voting — ' . $event->name)

@section('topbar-actions')
    <a href="{{ route('moderator.voting.export', $event) }}" class="btn btn-secondary btn-sm">Export CSV</a>
    @if($event->voting_closed)
        <form method="POST" action="{{ route('moderator.voting.reopen', $event) }}">@csrf<button class="btn btn-outline btn-sm">Reopen Voting</button></form>
    @else
        <form method="POST" action="{{ route('moderator.voting.close', $event) }}">@csrf<button class="btn btn-outline btn-sm">Close Voting</button></form>
    @endif
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall</a>
@endsection

@section('content')

@include('admin.voting._body')

@if($votes->count())
<div class="card">
    <div class="card-header">
        <h3>Individual Votes</h3>
        <form method="POST" action="{{ route('moderator.voting.reset', $event) }}" onsubmit="return confirm('This will delete ALL votes. Are you sure?')">
            @csrf <button class="btn btn-danger btn-sm">Reset All Votes</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Voted For</th>
                    <th>Session</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($votes as $vote)
                <tr>
                    <td class="text-muted">{{ $vote->id }}</td>
                    <td class="font-bold">{{ $vote->candidate_name }}</td>
                    <td class="text-muted text-xs">{{ Str::limit($vote->voter_session, 16) }}</td>
                    <td class="text-muted text-xs">{{ $vote->created_at->format('d M, H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($votes->hasPages())
    <div style="padding:16px 20px">{{ $votes->links() }}</div>
    @endif
</div>
@endif

@endsection
