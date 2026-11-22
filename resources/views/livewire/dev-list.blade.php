<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        <label class="sr-only" for="dev-status">{{ __('common.status') }}</label>
        <select id="dev-status" wire:model.live="status" class="input">
            <option value="">{{ __('common.all') }}</option>
            @foreach(App\Enums\TicketStatus::cases() as $case)<option value="{{ $case->value }}">{{ __('status.'.$case->value) }}</option>@endforeach
        </select>
        <label class="sr-only" for="dev-assignee">{{ __('common.assignee') }}</label>
        <select id="dev-assignee" wire:model.live="assignee" class="input">
            <option value="">{{ __('common.all') }}</option>
            @foreach($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach
        </select>
        <label class="sr-only" for="dev-search">{{ __('common.search') }}</label>
        <input id="dev-search" wire:model.live.debounce.250ms="search" class="input flex-1" placeholder="{{ __('common.search') }}">
        @if($filters->isNotEmpty())
            <label class="sr-only" for="saved-filter">{{ __('dev.saved_filters') }}</label>
            <select id="saved-filter" class="input" wire:change="$wire.applySavedFilter($event.target.value)">
                <option value="0">{{ __('dev.saved_filters') }}</option>
                @foreach($filters as $filter)<option value="{{ $filter->id }}">{{ $filter->name }}</option>@endforeach
            </select>
        @endif
    </div>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('common.title') }}</th><th>{{ __('common.status') }}</th><th>{{ __('common.priority') }}</th><th>{{ __('common.assignee') }}</th><th>{{ __('dev.estimate') }}</th></tr></thead>
            <tbody>@foreach($tickets as $ticket)
                <tr class="border-t border-slate-100">
                    <td><a class="text-brand-700 hover:underline" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->key }} — {{ $ticket->title }}</a></td>
                    <td><x-status-badge :status="$ticket->status" /></td>
                    <td>{{ __('priorities.'.$ticket->priority->value) }}</td>
                    <td>{{ $ticket->assignee?->name ?? '—' }}</td>
                    <td>{{ $ticket->estimate ?? '—' }}</td>
                </tr>
            @endforeach</tbody>
        </table>
    </div>
    {{ $tickets->links() }}
</div>
