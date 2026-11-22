@props(['status'])
@php
    use App\Enums\StatusCategory;
    use App\Enums\TicketStatus;

    $case = $status instanceof TicketStatus ? $status : TicketStatus::from((string) $status);
    $category = $case->category();
@endphp
{{-- One label, one icon, one colour for every database status. The legacy
     "requester" prop is still accepted by call sites but is intentionally
     ignored: two badges with the same text must never look different. --}}
<x-badge :tone="$category->tone()" class="gap-1.5" :title="__($category->labelKey())">
    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        {!! $category->iconPath() !!}
    </svg>
    <span>{{ __($category->labelKey()) }}</span>
</x-badge>
