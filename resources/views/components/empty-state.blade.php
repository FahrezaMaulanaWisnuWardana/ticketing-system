@props([
    'title' => null,
    'description' => null,
    'icon' => 'inbox',
    'action' => null,
    'href' => null,
])
@php
    $paths = [
        'inbox' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 6 5.25h12l2.25 8.25v5.25a1.5 1.5 0 0 1-1.5 1.5h-15a1.5 1.5 0 0 1-1.5-1.5V13.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5h4.5l1.5 3h4.5l1.5-3h4.5"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="m16 16 4 4"/>',
    ];
@endphp
<div class="card text-center">
    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            {!! $paths[$icon] ?? $paths['inbox'] !!}
        </svg>
    </span>
    <p class="mt-3 font-medium text-slate-700 dark:text-slate-200">{{ $title ?? __('common.empty_title') }}</p>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description ?? __('common.empty_hint') }}</p>
    @if($action && $href)
        <a href="{{ $href }}" class="btn btn-primary mt-4">{{ $action }}</a>
    @endif
    {{ $slot }}
</div>
