@props(['paginator'])
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $start = max(1, $current - 1);
    $end = min($last, $current + 1);
    $pageUrls = $paginator->getUrlRange($start, $end);
@endphp
<nav role="navigation" aria-label="{{ __('portal.pagination.label') }}" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-800 px-3 py-3">
    <p class="text-xs text-slate-500 dark:text-slate-400">
        {{ __('portal.pagination.showing', [
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
            'total' => $paginator->total(),
        ]) }}
    </p>

    <div class="flex items-center gap-1">
        @if($paginator->onFirstPage())
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 dark:text-slate-600" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.25 6-6 6 6 6"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
               aria-label="{{ __('portal.pagination.previous') }}" title="{{ __('portal.pagination.previous') }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.25 6-6 6 6 6"/></svg>
            </a>
        @endif

        @if($start > 1)
            <a href="{{ $paginator->url(1) }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="{{ __('portal.pagination.page', ['page' => 1]) }}">1</a>
            @if($start > 2)<span class="px-1 text-sm text-slate-400" aria-hidden="true">…</span>@endif
        @endif

        @foreach($pageUrls as $page => $url)
            @if($page === $current)
                <span aria-current="page" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-brand-600 px-2 text-sm font-semibold text-white">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                   aria-label="{{ __('portal.pagination.page', ['page' => $page]) }}">{{ $page }}</a>
            @endif
        @endforeach

        @if($end < $last)
            @if($end < $last - 1)<span class="px-1 text-sm text-slate-400" aria-hidden="true">…</span>@endif
            <a href="{{ $paginator->url($last) }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="{{ __('portal.pagination.page', ['page' => $last]) }}">{{ $last }}</a>
        @endif

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
               aria-label="{{ __('portal.pagination.next') }}" title="{{ __('portal.pagination.next') }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 6 6 6-6 6"/></svg>
            </a>
        @else
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 dark:text-slate-600" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 6 6 6-6 6"/></svg>
            </span>
        @endif
    </div>
</nav>
