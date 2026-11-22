@props(['tone' => 'slate'])
@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-700',
        'blue' => 'bg-blue-100 text-blue-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'emerald' => 'bg-emerald-100 text-emerald-800',
        'rose' => 'bg-rose-100 text-rose-800',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'badge '.($tones[$tone] ?? $tones['slate'])]) }}>{{ $slot }}</span>
