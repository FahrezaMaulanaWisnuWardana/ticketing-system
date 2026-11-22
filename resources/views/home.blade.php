@extends('layouts.app')
@section('title', __('portal.nav.home'))
@section('content')
@php
    $navUser = auth()->user();
    $allRequestsUrl = $navUser->isRequester()
        ? route('requester.requests')
        : route('agent.queue', ['queue' => 'mine']);
@endphp

{{-- Hero / live search ------------------------------------------------ --}}
<section class="mb-10" x-data="portalSearch('{{ route('portal.suggest') }}')" x-on:click.outside="open = false">
    <div class="mx-auto max-w-3xl text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">{{ __('portal.home.eyebrow') }}</p>
        <h1 class="mt-2 text-3xl font-semibold sm:text-4xl">{{ __('portal.home.heading') }}</h1>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-slate-600 dark:text-slate-400 sm:text-base">{{ __('portal.home.subheading') }}</p>
    </div>

    <div class="relative mx-auto mt-7 max-w-2xl">
        <label class="sr-only" for="portal-hero-search">{{ __('portal.home.search_label') }}</label>
        <div class="relative">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="m16 16 4 4"/>
            </svg>
            <input id="portal-hero-search" type="search" autocomplete="off"
                   x-model="query" x-on:input.debounce.300ms="run()"
                   x-on:focus="open = query.length > 0"
                   x-on:keydown.escape.window="open = false"
                   role="combobox" aria-expanded="false" aria-controls="portal-suggestions"
                   placeholder="{{ __('portal.home.search_placeholder') }}"
                   class="input w-full py-3.5 pl-12 pr-4 text-base shadow-sm">
        </div>

        <div id="portal-suggestions" x-show="open" x-cloak role="listbox"
             aria-label="{{ __('portal.suggest.aria') }}"
             class="absolute z-30 mt-2 max-h-[60vh] w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <template x-if="loading">
                <p class="px-3 py-3 text-sm text-slate-500 dark:text-slate-400">{{ __('portal.home.searching') }}</p>
            </template>
            <template x-if="! loading && results && results.types.length === 0 && results.articles.length === 0">
                <p class="px-3 py-3 text-sm text-slate-500 dark:text-slate-400">{{ __('portal.suggest.empty') }}</p>
            </template>
            <template x-if="! loading && results && results.types.length > 0">
                <div>
                    <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('portal.home.results_types') }}</p>
                    <template x-for="type in results.types" :key="type.key">
                        <a :href="type.url" role="option" class="flex items-start gap-3 rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">
                            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><g x-html="type.icon"></g></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-slate-900 dark:text-slate-100" x-text="type.label"></span>
                                <span class="block truncate text-xs text-slate-500 dark:text-slate-400" x-text="type.description"></span>
                            </span>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="! loading && results && results.articles.length > 0">
                <div>
                    <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('portal.home.results_articles') }}</p>
                    <template x-for="article in results.articles" :key="article.key">
                        <a :href="article.url" role="option" class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">
                            <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 4.5h9l4.5 4.5v10.5a.75.75 0 0 1-.75.75h-12a.75.75 0 0 1-.75-.75V5.25a.75.75 0 0 1 .75-.75Z"/></svg>
                            <span class="truncate text-sm text-slate-700 dark:text-slate-200" x-text="article.label"></span>
                        </a>
                    </template>
                </div>
            </template>
        </div>

        <p class="mt-3 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('portal.home.search_hint') }}</p>
    </div>
</section>

{{-- Request types ----------------------------------------------------- --}}
<section aria-labelledby="portal-types-heading" class="mb-10">
    <div class="mb-4">
        <h2 id="portal-types-heading" class="text-xl font-semibold">{{ __('portal.home.types_heading') }}</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('portal.home.types_subheading') }}</p>
    </div>

    <div class="space-y-7">
        @foreach($groupedTypes as $group => $types)
            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ __($groupLabels[$group] ?? '') }}
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($types as $type)
                        <x-request-type-card :type="$type" />
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- Recent requests (no table) --------------------------------------- --}}
<section aria-labelledby="portal-recent-heading">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 id="portal-recent-heading" class="text-lg font-semibold">{{ __('portal.home.recent_heading') }}</h2>
        <a href="{{ $allRequestsUrl }}" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">{{ __('portal.home.view_all') }} →</a>
    </div>

    @if($recent->isEmpty())
        <div class="card">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('portal.home.recent_empty') }}</p>
        </div>
    @else
        <ul class="card divide-y divide-slate-100 !p-0 dark:divide-slate-800">
            @foreach($recent as $ticket)
                <li class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3">
                    <span translate="no" class="shrink-0 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $ticket->key }}</span>
                    <a href="{{ route('tickets.show', $ticket) }}"
                       class="min-w-0 flex-1 truncate font-medium text-slate-900 hover:text-brand-600 dark:text-slate-100 dark:hover:text-brand-400"
                       title="{{ $ticket->title }}">{{ $ticket->title }}</a>
                    <x-status-badge :status="$ticket->status" />
                    <time class="shrink-0 text-xs text-slate-500 dark:text-slate-400"
                          title="{{ App\Support\DateFormat::dateTime($ticket->updated_at) }}"
                          datetime="{{ $ticket->updated_at->toIso8601String() }}">{{ App\Support\DateFormat::format($ticket->updated_at) }}</time>
                </li>
            @endforeach
        </ul>
    @endif
</section>
@endsection
