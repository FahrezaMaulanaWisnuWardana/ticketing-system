@props(['priority'])
@php
    use App\Enums\Priority;

    $case = $priority instanceof Priority ? $priority : Priority::from((string) $priority);
@endphp
<x-badge :tone="$case->tone()" @class(['gap-1.5', 'font-bold ring-1 ring-inset ring-red-200 dark:ring-red-800' => $case->isUrgent()])>
    <svg @class(['h-3.5 w-3.5 shrink-0', 'fill-current' => $case->isUrgent()]) viewBox="0 0 24 24"
         @if(! $case->isUrgent()) fill="none" stroke="currentColor" stroke-width="1.8" @endif aria-hidden="true">
        {!! $case->iconPath() !!}
    </svg>
    <span>{{ __('priorities.'.$case->value) }}</span>
</x-badge>
