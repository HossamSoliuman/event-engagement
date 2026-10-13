@extends('layouts.admin')
@section('title','Voting — '.$event->name)
@section('page-title')
    <i data-lucide="trophy" class="lucide-icon"></i> Voting Results
@endsection

@section('topbar-actions')
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
    <a href="{{ route('admin.voting.export',$event) }}" class="btn btn-ghost btn-sm"><i data-lucide="download" class="lucide-icon"></i> CSV</a>
    @if($event->voting_closed)
        <form method="POST" action="{{ route('admin.voting.reopen',$event) }}">@csrf<button class="btn btn-success btn-sm"><i data-lucide="lock-open" class="lucide-icon"></i> Reopen Voting</button></form>
    @else
        <form method="POST" action="{{ route('admin.voting.close',$event) }}">@csrf<button class="btn btn-danger btn-sm"><i data-lucide="lock" class="lucide-icon"></i> Close Voting</button></form>
    @endif
    <a href="{{ route('admin.events.show',$event) }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="lucide-icon"></i> Event</a>
@endsection

@section('content')

@if($event->voting_closed)
<div class="alert alert-info"><i data-lucide="lock" class="lucide-icon"></i> Voting is closed. Guests can no longer cast votes.</div>
@endif

@include('admin.voting._body')

@if($totalVotes > 0)
<div class="card" style="border-color:rgba(239,68,68,.25)">
    <div class="card-header"><h3 style="color:var(--red)"><i data-lucide="alert-triangle" class="lucide-icon"></i> Danger Zone</h3></div>
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <div class="font-bold">Reset All Votes</div>
            <div class="text-muted text-xs">This permanently deletes all {{ $totalVotes }} votes and cannot be undone.</div>
        </div>
        <form method="POST" action="{{ route('admin.voting.reset',$event) }}"
              onsubmit="return confirm('Delete ALL votes? This cannot be undone.')">
            @csrf <button class="btn btn-danger btn-sm"><i data-lucide="trash-2" class="lucide-icon"></i> Reset All Votes</button>
        </form>
    </div>
</div>
@endif

@endsection
