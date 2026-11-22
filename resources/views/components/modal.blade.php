@props(['title' => '', 'show' => 'false'])
<div x-show="{{ $show }}" x-cloak class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 pt-16">
    <div class="card w-full max-w-lg" role="dialog" aria-modal="true" aria-label="{{ $title }}">
        <h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>
        {{ $slot }}
    </div>
</div>
