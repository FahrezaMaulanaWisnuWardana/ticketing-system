<div class="space-y-4">
    <label class="sr-only" for="board-sprint">{{ __('dev.sprints') }}</label>
    <select id="board-sprint" wire:model.live="sprintId" class="input">
        <option value="">{{ __('dev.all_sprints') }}</option>
        @foreach($sprints as $sprint)<option value="{{ $sprint->id }}">{{ $sprint->name }} ({{ $sprint->state }})</option>@endforeach
    </select>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($columns as $column)
            <div class="card min-h-40">
                <h2 class="mb-3 font-semibold">{{ __('status.'.$column->value) }}</h2>
                <div class="space-y-3">
                    @forelse($tickets->get($column->value, collect()) as $ticket)
                        <article x-data="{ status: '{{ $ticket->status->value }}' }" class="rounded-lg border border-slate-200 p-3">
                            <a class="text-sm font-medium text-brand-700 hover:underline" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->key }}</a>
                            <p class="mt-1 text-sm">{{ $ticket->title }}</p>
                            <p class="mt-2 text-xs text-slate-500">{{ $ticket->assignee?->name ?? __('common.assignee') }} · <span class="tabular">{{ $ticket->estimate ?? '—' }} pts</span></p>
                            @can('changeStatus', $ticket)
                                <label class="sr-only" for="move-{{ $ticket->id }}">{{ __('common.status') }}</label>
                                <select id="move-{{ $ticket->id }}" class="input mt-2 w-full text-xs" :value="status"
                                        @@change="const previous = status; status = $event.target.value; $wire.moveTicket({{ $ticket->id }}, status).catch(() => { status = previous; })">
                                    <option value="{{ $ticket->status->value }}">{{ __('status.'.$ticket->status->value) }}</option>
                                    @foreach(App\Domain\TicketWorkflow::allowedNext($ticket->status) as $next)
                                        @if($next !== $column)<option value="{{ $next->value }}">{{ __('status.'.$next->value) }}</option>@endif
                                    @endforeach
                                </select>
                            @endcan
                        </article>
                    @empty
                        <p class="text-xs text-slate-400">{{ __('common.empty_hint') }}</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
