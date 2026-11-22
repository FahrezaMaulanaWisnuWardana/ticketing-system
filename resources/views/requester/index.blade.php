@extends('layouts.app')
@section('title', __('common.my_requests'))
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">{{ __('common.my_requests') }}</h1>
    <a href="{{ route('tickets.create') }}" class="btn btn-primary">+ {{ __('tickets.new_request') }}</a>
</div>

<div class="mb-4 flex gap-2">
    <a href="{{ route('requester.requests', ['filter' => 'open']) }}" class="btn {{ $filter === 'open' ? 'btn-primary' : 'btn-secondary' }}">{{ __('common.open') }}</a>
    <a href="{{ route('requester.requests', ['filter' => 'resolved']) }}" class="btn {{ $filter === 'resolved' ? 'btn-primary' : 'btn-secondary' }}">{{ __('common.resolved') }}</a>
    <form method="GET" action="{{ route('requester.requests') }}" class="flex-1">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <label class="sr-only" for="request-search">{{ __('common.search') }}</label>
        <input id="request-search" name="q" value="{{ request('q') }}" class="input w-full" placeholder="{{ __('common.search') }}">
    </form>
</div>

@if($tickets->isEmpty())
    <x-empty-state :action="__('tickets.new_request')" :href="route('tickets.create')" />
@else
<div class="card overflow-x-auto">
    <table class="table">
        <thead><tr><th>{{ __('common.title') }}</th><th>{{ __('common.project') }}</th><th>{{ __('common.status') }}</th><th>{{ __('common.updated_at') }}</th></tr></thead>
        <tbody>@foreach($tickets as $ticket)
            <tr class="border-t border-slate-100">
                <td><a class="font-medium text-brand-700 hover:underline" href="{{ route('requester.requests.show', $ticket) }}">{{ $ticket->key }} — {{ $ticket->title }}</a></td>
                <td>{{ $ticket->project?->key }}</td>
                <td><x-status-badge :status="$ticket->status" requester /></td>
                <td>{{ $ticket->updated_at->timezone(config('ticketing.display_timezone'))->format('d M Y H:i') }}</td>
            </tr>
        @endforeach</tbody>
    </table>
</div>
{{ $tickets->links() }}
@endif
@endsection
