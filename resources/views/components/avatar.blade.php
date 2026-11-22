@props(['name' => null, 'size' => 'sm'])
@php
    $palette = [
        'bg-brand-100 text-brand-700 dark:bg-brand-900/50 dark:text-brand-200',
        'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-200',
        'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-200',
        'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200',
        'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200',
    ];

    $clean = trim((string) $name);
    $words = $clean === '' ? [] : (preg_split('/\s+/u', $clean) ?: []);
    $initials = $words === []
        ? ''
        : mb_strtoupper(mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : ''));
    $color = $words === [] ? 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' : $palette[crc32($clean) % count($palette)];
    $dimensions = $size === 'lg' ? 'h-9 w-9 text-xs' : 'h-7 w-7 text-[11px]';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full font-semibold uppercase '.$dimensions.' '.$color, 'title' => $clean, 'aria-hidden' => 'true']) }}>
    @if($initials === '')
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="8" r="3.25"/><path stroke-linecap="round" d="M5.25 19.5a6.75 6.75 0 0 1 13.5 0"/>
        </svg>
    @else
        {{ $initials }}
    @endif
</span>
