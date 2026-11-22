@props(['title' => '', 'show' => 'false'])
<div x-show="{{ $show }}" x-cloak class="modal-overlay fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 pt-16"
     x-transition:enter="ui-overlay-enter-active" x-transition:enter-start="ui-overlay-enter-start" x-transition:enter-end="ui-overlay-enter-end"
     x-transition:leave="ui-overlay-leave-active" x-transition:leave-start="ui-overlay-leave-start" x-transition:leave-end="ui-overlay-leave-end">
    <div class="modal-panel card w-full max-w-lg" role="dialog" aria-modal="true" aria-label="{{ $title }}"
         x-transition:enter="ui-enter-active" x-transition:enter-start="ui-enter-start" x-transition:enter-end="ui-enter-end"
         x-transition:leave="ui-leave-active" x-transition:leave-start="ui-leave-start" x-transition:leave-end="ui-leave-end">
        <h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>
        {{ $slot }}
    </div>
</div>
