@php
    $pageName = $paginator->getPageName();
@endphp
<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 dark:border-slate-700 px-3 py-3">
    <p class="text-sm text-slate-600 dark:text-slate-400">
        {{ __('tickets.showing', ['from' => $paginator->firstItem() ?? 0, 'to' => $paginator->lastItem() ?? 0, 'total' => $paginator->total()]) }}
    </p>

    <nav role="navigation" aria-label="{{ __('tickets.pagination') }}" class="flex items-center gap-1">
        @if($paginator->onFirstPage())
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 dark:text-slate-600" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.25 6-6 6 6 6"/></svg>
            </span>
        @else
            <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled"
                    aria-label="{{ __('tickets.previous') }}" title="{{ __('tickets.previous') }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.25 6-6 6 6 6"/></svg>
            </button>
        @endif

        @foreach($elements as $element)
            @if(is_string($element))
                <span class="px-1 text-sm text-slate-400" aria-hidden="true">{{ $element }}</span>
            @elseif(is_array($element))
                @foreach($element as $page => $url)
                    @if($page === $paginator->currentPage())
                        <span aria-current="page" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-brand-600 px-2 text-sm font-semibold text-white">{{ $page }}</span>
                    @else
                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" wire:loading.attr="disabled"
                                aria-label="{{ __('tickets.page') }} {{ $page }}"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if($paginator->hasMorePages())
            <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled"
                    aria-label="{{ __('tickets.next') }}" title="{{ __('tickets.next') }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 6 6 6-6 6"/></svg>
            </button>
        @else
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 dark:text-slate-600" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 6 6 6-6 6"/></svg>
            </span>
        @endif
    </nav>

    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400" for="ticket-per-page">
        <span>{{ __('tickets.per_page') }}</span>
        <select id="ticket-per-page" wire:model.live="perPage" class="input w-auto py-1">
            @foreach([25, 50, 100] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
        </select>
    </label>
</div>
