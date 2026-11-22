@extends('layouts.app')
@section('title', __('portal.list.title'))
@section('content')
@php
    $filters = $filters + [
        'creator' => 'mine', 'status' => 'open', 'type' => null, 'q' => null,
        'sort' => 'created_at', 'dir' => 'desc',
    ];
    $hasActiveFilters = $filters['status'] !== 'open'
        || $filters['type'] !== null
        || $filters['q'] !== null
        || $filters['creator'] === 'all'
        || $filters['sort'] !== 'created_at'
        || $filters['dir'] !== 'desc';
    $queryParams = array_filter([
        'creator' => $filters['creator'],
        'status' => $filters['status'],
        'type' => $filters['type'],
        'q' => $filters['q'],
    ], static fn ($value) => $value !== null && $value !== '');
@endphp

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold">{{ __('portal.list.title') }}</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('portal.list.subtitle') }}</p>
    </div>
    @can('create', App\Models\Ticket::class)
        <a href="{{ route('tickets.create') }}" class="btn btn-primary">+ {{ __('portal.empty.action') }}</a>
    @endcan
</div>

<div x-data="{ loading: false }">
    <x-filter-bar :action="route('requester.requests')" :filters="$filters" :type-options="$typeOptions"
                  :show-creator="$showCreator" :has-active-filters="$hasActiveFilters"
                  :reset-url="route('requester.requests')" id-prefix="request"
                  x-on:submit="loading = true" />

    <div x-show="loading" x-cloak class="card !p-0" aria-hidden="true">
        @for($i = 0; $i < 6; $i++)
            <div class="flex animate-pulse items-center gap-4 border-b border-slate-100 px-3 py-3 last:border-0 dark:border-slate-800">
                <div class="h-4 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
                <div class="h-4 flex-1 rounded bg-slate-200 dark:bg-slate-700"></div>
                <div class="h-5 w-28 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                <div class="h-4 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
            </div>
        @endfor
    </div>

    <div x-show="! loading" class="mt-4 space-y-3">
        @if($tickets->isEmpty())
            <x-empty-state :title="__($hasActiveFilters ? 'portal.empty.filtered_title' : 'portal.empty.title')"
                           :description="__($hasActiveFilters ? 'portal.empty.filtered_hint' : 'portal.empty.hint')"
                           :icon="$hasActiveFilters ? 'search' : 'inbox'">
                @if($hasActiveFilters)
                    <a href="{{ route('requester.requests') }}" class="btn btn-secondary mt-4">{{ __('portal.list.reset') }}</a>
                @endif
                @can('create', App\Models\Ticket::class)
                    <a href="{{ route('tickets.create') }}" class="btn btn-primary mt-4">{{ __('portal.empty.action') }}</a>
                @endcan
            </x-empty-state>
        @else
            <p class="sr-only" role="status" aria-live="polite">{{ __('portal.list.results_count', ['count' => $tickets->total()]) }}</p>
            <x-ticket-table :tickets="$tickets" variant="requester" :sort="$filters['sort']" :direction="$filters['dir']"
                            route-name="requester.requests" :query-params="$queryParams" />
            <x-pagination :paginator="$tickets" />
        @endif
    </div>
</div>
@endsection
