@props(['field', 'label', 'sort', 'direction'])
@php
    $active = $sort === $field;
    $ariaSort = $active ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none';
@endphp
<th scope="col" aria-sort="{{ $ariaSort }}" {{ $attributes->merge(['class' => 'whitespace-nowrap px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400']) }}>
    <button type="button" wire:click="sortBy('{{ $field }}')"
            title="{{ __('tickets.sort_by') }}: {{ $label }}"
            class="group inline-flex items-center gap-1 rounded focus-visible:ring-2 focus-visible:ring-brand-500">
        <span>{{ $label }}</span>
        <svg @class([
                'h-3.5 w-3.5 transition-transform',
                'text-brand-600 dark:text-brand-400' => $active,
                'text-slate-400 opacity-60 group-hover:opacity-100' => ! $active,
                'rotate-180' => $active && $direction === 'asc',
            ]) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
        </svg>
    </button>
</th>
