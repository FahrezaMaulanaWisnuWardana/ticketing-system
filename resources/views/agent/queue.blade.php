@extends('layouts.app')
@section('title', __('portal.queues.title'))
@section('content')
@php
    $queues = [
        'all_open' => 'portal.queues.all_open',
        'mine' => 'portal.queues.mine',
        'unassigned' => 'portal.queues.unassigned',
        'new' => 'portal.queues.new',
        'recently_resolved' => 'portal.queues.recently_resolved',
    ];
    $currentQueue = $filters['queue'] ?? 'all_open';
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-semibold">{{ __('portal.queues.title') }}</h1>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('portal.queues.subtitle') }}</p>
</div>

<nav aria-label="{{ __('portal.queues.title') }}" class="mb-4 flex flex-wrap gap-2">
    @foreach($queues as $key => $label)
        <a href="{{ route('agent.queue', ['queue' => $key]) }}"
           @class([
               'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium',
               'bg-brand-600 text-white' => $currentQueue === $key,
               'bg-white text-slate-600 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' => $currentQueue !== $key,
           ])>
            {{ __($label) }}
            <span @class([
                'tabular rounded-full px-2 py-0.5 text-[11px]',
                'bg-white/20 text-white' => $currentQueue === $key,
                'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => $currentQueue !== $key,
            ])>{{ $counts[$key] ?? 0 }}</span>
        </a>
    @endforeach
</nav>

<form method="GET" action="{{ route('agent.queue') }}" role="search" class="card !p-3">
    <div class="flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-56">
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400" for="queue-select">{{ __('portal.queues.title') }}</label>
            <select id="queue-select" name="queue" class="input w-full py-1.5">
                @foreach($queues as $key => $label)
                    <option value="{{ $key }}" @selected($currentQueue === $key)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-0 flex-1">
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400" for="queue-search">{{ __('portal.list.search') }}</label>
            <input id="queue-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="{{ __('portal.list.search_placeholder') }}" class="input w-full py-1.5">
        </div>
        <button type="submit" class="btn btn-secondary py-1.5">{{ __('common.filter') }}</button>
    </div>
</form>

<div x-data="{ all: false, bulkAction: 'assign' }" class="mt-4">
    @if($tickets->isEmpty())
        <x-empty-state :title="__('portal.queues.empty')" :description="__('portal.queues.empty_hint')" icon="inbox" />
    @else
        <form id="bulk-form" method="POST" action="{{ route('agent.queue.bulk.assign') }}">
            @csrf
            <input type="hidden" name="action" x-model="bulkAction">
            <x-ticket-table :tickets="$tickets" variant="agent" :query-params="['queue' => $currentQueue, 'q' => $filters['q'] ?? null]"
                            selectable route-name="agent.queue" />

            <div class="card mt-3 flex flex-wrap items-end gap-3 !p-3">
                <div class="w-full sm:w-56">
                    <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400" for="bulk-assignee">{{ __('portal.queues.assignee') }}</label>
                    <select id="bulk-assignee" name="assignee_id" class="input w-full py-1.5">
                        <option value="">{{ __('portal.queues.select_assignee') }}</option>
                        @foreach($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected($assignee->id === auth()->id())>{{ $assignee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400" for="bulk-status">{{ __('common.status') }}</label>
                    <select id="bulk-status" name="status" class="input w-full py-1.5">
                        <option value="">{{ __('portal.queues.choose_status') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}">{{ __($status->labelKey()) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary py-1.5" x-on:click="bulkAction = 'assign'">{{ __('portal.queues.bulk_assign') }}</button>
                <button type="submit" class="btn btn-secondary py-1.5" formaction="{{ route('agent.queue.bulk.status') }}" x-on:click="bulkAction = 'status'">{{ __('portal.queues.bulk_status') }}</button>
            </div>
        </form>
        <x-pagination :paginator="$tickets" />
    @endif
</div>
@endsection
