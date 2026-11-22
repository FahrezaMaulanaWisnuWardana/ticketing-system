@extends('layouts.app')
@section('title', __('tickets.confirmation_title'))
@section('content')
<div class="card max-w-xl">
    <h1 class="text-xl font-semibold">{{ __('tickets.confirmation_title') }}</h1>
    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ __('tickets.confirmation_line', ['key' => $ticket->key]) }}</p>
    <p class="mt-3">{{ __('tickets.expected_response') }}: <strong>{{ $responseTarget }}</strong></p>
    <div class="mt-5 flex gap-2">
        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-primary">{{ __('notifications.view_ticket') }}</a>
        <a href="{{ route('requester.requests') }}" class="btn btn-secondary">{{ __('common.my_requests') }}</a>
    </div>
</div>
@endsection
