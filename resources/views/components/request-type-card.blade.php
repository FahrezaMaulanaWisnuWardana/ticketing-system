@props(['type', 'compact' => false])
<a href="{{ $type['url'] }}"
   @class([
       'card group flex min-w-0 items-start gap-3 no-underline transition-colors',
       'p-3' => $compact,
       'p-4 hover:border-brand-300 dark:hover:border-brand-700' => ! $compact,
   ])>
    <span @class([
        'inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300',
        'h-8 w-8' => $compact,
        'h-10 w-10' => ! $compact,
    ])>
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            {!! $type['icon'] !!}
        </svg>
    </span>
    <span class="min-w-0 flex-1">
        <span @class([
            'block font-medium text-slate-900 dark:text-slate-100',
            'text-sm group-hover:text-brand-700 dark:group-hover:text-brand-300' => ! $compact,
        ])>{{ $type['label'] }}</span>
        @unless($compact)
            <span class="mt-1 block text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $type['description'] }}</span>
        @endunless
    </span>
</a>
