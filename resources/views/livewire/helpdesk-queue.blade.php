<div class="grid gap-6 lg:grid-cols-3" x-data="{}">
    <div class="lg:col-span-2 space-y-4">
        <div class="flex flex-wrap gap-3">
            <label class="sr-only" for="queue-search">{{ __('common.search') }}</label>
            <input id="queue-search" wire:model.live.debounce.250ms="search" class="input flex-1" placeholder="{{ __('common.search') }}">
            <button type="button" class="btn btn-secondary" x-shortcut="a" wire:click="assign({{ $tickets->first()?->id ?? 0 }})" @if($tickets->isEmpty()) disabled @endif>
                {{ __('common.assign_to_me') }} (A)
            </button>
        </div>

        @if($tickets->isEmpty())
            <x-empty-state />
        @else
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>{{ __('common.title') }}</th><th>{{ __('common.priority') }}</th><th>{{ __('common.requester') }}</th><th>{{ __('common.created_at') }}</th><th>{{ __('common.actions') }}</th></tr></thead>
                <tbody>@foreach($tickets as $ticket)
                    <tr class="border-t border-slate-100 {{ $activeTicketId === $ticket->id ? 'bg-brand-50' : '' }}">
                        <td>
                            <a class="font-medium text-brand-700 hover:underline" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->key }} — {{ $ticket->title }}</a>
                            <div class="text-xs text-slate-500">{{ $ticket->project?->key }}</div>
                        </td>
                        <td><x-badge :tone="$ticket->priority->value === 'urgent' ? 'rose' : 'slate'">{{ __('priorities.'.$ticket->priority->value) }}</x-badge></td>
                        <td>{{ $ticket->requester?->name ?? '—' }}</td>
                        <td>{{ $ticket->created_at->timezone(config('ticketing.display_timezone'))->diffForHumans() }}</td>
                        <td>
                            <button class="btn btn-secondary text-xs" wire:click="assign({{ $ticket->id }})">{{ __('common.assign_to_me') }}</button>
                            <button class="btn btn-ghost text-xs" wire:click="$set('activeTicketId', {{ $ticket->id }})">{{ __('common.reply') }}</button>
                        </td>
                    </tr>
                @endforeach</tbody>
            </table>
        </div>
        {{ $tickets->links() }}
        @endif
    </div>

    <div class="card h-fit">
        <h2 class="mb-3 font-semibold">{{ __('common.reply') }}</h2>
        @if($activeTicketId === null)
            <p class="text-sm text-slate-500">{{ __('queue.select_ticket') }}</p>
        @else
            @if($canned->isNotEmpty())
                <label class="mb-1 block text-sm font-medium" for="canned">{{ __('canned.title') }}</label>
                <select id="canned" class="input mb-3 w-full" wire:change="selectCanned($event.target.value)">
                    <option value="0">—</option>
                    @foreach($canned as $reply)<option value="{{ $reply->id }}">{{ $reply->title }}</option>@endforeach
                </select>
            @endif
            <form wire:submit="send" class="space-y-3">
                <label class="sr-only" for="queue-reply">{{ __('common.reply') }}</label>
                <textarea id="queue-reply" wire:model="replyBody" rows="6" class="input w-full"></textarea>
                @error('replyBody')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
                <label class="sr-only" for="queue-files">{{ __('common.attachments') }}</label>
                <input id="queue-files" type="file" wire:model="files" multiple class="input w-full text-xs">
                <button class="btn btn-primary w-full" type="submit">{{ __('common.send') }}</button>
            </form>
        @endif
    </div>
</div>
