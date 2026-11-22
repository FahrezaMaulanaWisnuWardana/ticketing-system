<div class="space-y-4">
    <div class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-52">
            <label class="sr-only" for="ticket-search">{{ __('common.search') }}</label>
            <input id="ticket-search" wire:model.live.debounce.250ms="search" class="input w-full" placeholder="{{ __('common.search') }}">
        </div>
        <div>
            <label class="sr-only" for="ticket-status">{{ __('common.status') }}</label>
            <select id="ticket-status" wire:model.live="status" class="input">
                <option value="">{{ __('common.all') }}</option>
                @foreach($statuses as $case)<option value="{{ $case->value }}">{{ __('status.'.$case->value) }}</option>@endforeach
            </select>
        </div>
    </div>

    @if($tickets->isEmpty())
        <x-empty-state :action="__('tickets.new_request')" :href="route('tickets.create')" />
    @else
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr>
                <th>{{ __('common.title') }}</th><th>{{ __('common.project') }}</th>
                <th>{{ __('common.status') }}</th><th>{{ __('common.priority') }}</th><th>{{ __('common.assignee') }}</th>
            </tr></thead>
            <tbody>
            @foreach($tickets as $row)
                <tr class="border-t border-slate-100">
                    <td><a class="font-medium text-brand-700 hover:underline" href="{{ route('tickets.show', $row) }}">{{ $row->key }} — {{ $row->title }}</a></td>
                    <td>{{ $row->project?->key }}</td>
                    <td><x-status-badge :status="$row->status" :requester="auth()->user()->isRequester()" /></td>
                    <td>{{ __('priorities.'.$row->priority->value) }}</td>
                    <td>{{ $row->assignee?->name ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $tickets->links() }}
    @endif
</div>
