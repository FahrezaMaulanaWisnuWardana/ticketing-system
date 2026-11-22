@extends('layouts.app')
@section('title', __('common.home'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ auth()->user()->isRequester() ? __('common.my_requests') : __('common.my_work') }}</h1>

@if($tickets->isEmpty())
    <x-empty-state />
@else
<div class="card overflow-x-auto">
    <table class="table">
        <thead><tr>
            <th>{{ __('common.title') }}</th><th>{{ __('common.project') }}</th>
            <th>{{ __('common.status') }}</th><th>{{ __('common.assignee') }}</th><th>{{ __('common.created_at') }}</th>
        </tr></thead>
        <tbody>
        @foreach($tickets as $ticket)
            <tr class="border-t border-slate-100">
                <td><a class="font-medium text-brand-700 hover:underline" href="{{ route('tickets.show', $ticket) }}"><span translate="no">{{ $ticket->key }}</span> — {{ $ticket->title }}</a></td>
                <td>{{ $ticket->project->key }}</td>
                <td><x-status-badge :status="$ticket->status" :requester="auth()->user()->isRequester()" /></td>
                <td>{{ $ticket->assignee?->name ?? '—' }}</td>
                <td>{{ App\Support\DateFormat::format($ticket->created_at) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $tickets->links() }}
@endif
@endsection
