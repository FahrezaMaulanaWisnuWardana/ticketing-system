@props(['tone' => 'slate'])
@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-100',
        'muted' => 'bg-slate-50 text-slate-600 ring-1 ring-inset ring-slate-200 dark:bg-slate-800/60 dark:text-slate-400 dark:ring-slate-700',
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
        'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
        'amber' => 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
        'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-200',
        'orange' => 'bg-orange-100 text-orange-900 dark:bg-orange-950 dark:text-orange-200',
        'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
        'rose' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'badge whitespace-nowrap '.($tones[$tone] ?? $tones['slate'])]) }}>{{ $slot }}</span>
