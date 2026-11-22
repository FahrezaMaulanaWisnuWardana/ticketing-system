@props(['status', 'requester' => false])
@php
    use App\Domain\StatusMapper;
    $label = $requester ? __(StatusMapper::labelKey($status)) : __('status.'.$status->value);
    $tone = $requester ? StatusMapper::tone($status) : match($status) {
        App\Enums\TicketStatus::New => 'blue',
        App\Enums\TicketStatus::Backlog, App\Enums\TicketStatus::Todo => 'slate',
        App\Enums\TicketStatus::InProgress, App\Enums\TicketStatus::InReview => 'indigo',
        App\Enums\TicketStatus::WaitingRequester => 'amber',
        App\Enums\TicketStatus::Done, App\Enums\TicketStatus::Closed => 'emerald',
    };
@endphp
<x-badge :tone="$tone">{{ $label }}</x-badge>
