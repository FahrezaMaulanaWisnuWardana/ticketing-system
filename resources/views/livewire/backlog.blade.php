<div x-data="{}" class="space-y-4">
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('dev.total_points') }}: <strong>{{ $points }}</strong></p>
        @if($sprint)<p class="text-xs text-slate-500 dark:text-slate-400">{{ __('dev.capacity') }}: {{ $sprint->capacity ?? '—' }}</p>@endif
    </div>

    <ul x-ref="list" class="space-y-2">
        @forelse($tickets as $ticket)
            <li data-id="{{ $ticket->id }}" class="card flex items-center justify-between">
                <div>
                    <a class="text-sm font-medium text-brand-700 dark:text-brand-300 hover:underline" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->key }}</a>
                    <span class="ml-2 text-sm">{{ $ticket->title }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-500 dark:text-slate-400 tabular">{{ $ticket->estimate ?? '—' }} pts</span>
                    @if($sprint && $ticket->kind->value === 'dev')
                        <button class="btn btn-secondary text-xs" wire:click="moveToSprint({{ $ticket->id }})">{{ __('dev.move_to_sprint') }}</button>
                    @endif
                </div>
            </li>
        @empty
            <x-empty-state />
        @endforelse
    </ul>

    @script
    <script>
        $wire.$on('refresh-backlog', () => {});
        const startSortable = (attempt = 0) => {
            const list = $refs.list;
            if (!list || !window.loadSortable) {
                if (attempt < 20) setTimeout(() => startSortable(attempt + 1), 50);
                return;
            }
            window.loadSortable().then((Sortable) => {
                Sortable.create(list, {
                    animation: 150,
                    onEnd: () => {
                        $wire.updateRanks(Array.from(list.children).map((item) => Number(item.dataset.id)));
                    },
                });
            });
        };
        startSortable();
    </script>
    @endscript
</div>
