@props(['status', 'requester' => false])
@php
    use App\Domain\StatusMapper;
    use App\Enums\TicketStatus;

    $case = $status instanceof TicketStatus ? $status : TicketStatus::from((string) $status);
    $shown = $case->group();
    // Requesters keep their plain-language labels; staff see the five canonical statuses.
    $label = $requester ? __(StatusMapper::labelKey($case)) : __($shown->labelKey());
    $tone = $requester ? StatusMapper::tone($case) : $shown->tone();
@endphp
<x-badge :tone="$tone" class="gap-1.5">
    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        {!! $shown->iconPath() !!}
    </svg>
    <span>{{ $label }}</span>
</x-badge>
