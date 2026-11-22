@props(['priority'])
@php
    use App\Enums\Priority;

    $case = $priority instanceof Priority ? $priority : Priority::from((string) $priority);
    $tones = [
        'red' => 'text-red-600 dark:text-red-400',
        'orange' => 'text-orange-600 dark:text-orange-400',
        'slate' => 'text-slate-500 dark:text-slate-400',
        'muted' => 'text-slate-400 dark:text-slate-500',
    ];
    $label = __('priorities.'.$case->value);
@endphp
<span class="inline-flex items-center gap-1.5" title="{{ $label }}">
    <svg @class(['h-4 w-4 shrink-0', $tones[$case->tone()] ?? $tones['slate'], 'fill-current' => $case->isUrgent()])
         viewBox="0 0 24 24"
         @if(! $case->isUrgent()) fill="none" stroke="currentColor" stroke-width="1.8" @endif aria-hidden="true">
        {!! $case->iconPath() !!}
    </svg>
    <span class="sr-only">{{ $label }}</span>
</span>
