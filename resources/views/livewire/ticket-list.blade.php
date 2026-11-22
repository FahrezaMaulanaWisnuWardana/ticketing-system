<div class="space-y-4">
    {{-- Filters only. The single global search lives in the top bar. --}}
    <div class="card !p-3">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-36 sm:w-40">
                <label for="filter-status" class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('common.status') }}</label>
                <select id="filter-status" wire:model.live="status" class="input w-full py-1.5">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($statuses as $case)
                        <option value="{{ $case->value }}">{{ __($case->labelKey()) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40 sm:w-48">
                <label for="filter-project" class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('common.project') }}</label>
                <select id="filter-project" wire:model.live="project" class="input w-full py-1.5">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($projects as $item)
                        <option value="{{ $item->id }}">{{ $item->key }} — {{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40 sm:w-48">
                <label for="filter-assignee" class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('common.assignee') }}</label>
                <select id="filter-assignee" wire:model.live="assignee" class="input w-full py-1.5">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($assignees as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36 sm:w-40">
                <label for="filter-priority" class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('common.priority') }}</label>
                <select id="filter-priority" wire:model.live="priority" class="input w-full py-1.5">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($priorities as $case)
                        <option value="{{ $case->value }}">{{ __('priorities.'.$case->value) }}</option>
                    @endforeach
                </select>
            </div>
            @if($hasActiveFilters)
                <button type="button" wire:click="resetFilters" class="btn btn-secondary py-1.5">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12a4.5 4.5 0 1 1-1.4-3.26M16.5 5.25V9h-3.75"/>
                    </svg>
                    {{ __('tickets.reset_filters') }}
                </button>
            @endif
        </div>
    </div>

    <p class="sr-only" role="status" aria-live="polite">
        {{ __('tickets.showing', ['from' => $tickets->firstItem() ?? 0, 'to' => $tickets->lastItem() ?? 0, 'total' => $tickets->total()]) }}
    </p>

    {{-- Skeleton rows while a Livewire request is in flight (no spinner). --}}
    <div wire:loading.delay class="card !p-0" aria-hidden="true">
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @for($i = 0; $i < 8; $i++)
                <div class="flex animate-pulse items-center gap-4 px-3 py-3">
                    <div class="h-3 w-20 rounded bg-slate-200 dark:bg-slate-700"></div>
                    <div class="h-3 flex-1 rounded bg-slate-200 dark:bg-slate-700"></div>
                    <div class="h-5 w-20 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                    <div class="h-5 w-24 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                    <div class="h-3 w-28 rounded bg-slate-200 dark:bg-slate-700"></div>
                    <div class="h-3 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
                </div>
            @endfor
        </div>
    </div>

    @if($tickets->isEmpty())
        <div wire:loading.class.delay="hidden" class="card text-center">
            <span class="mx-auto inline-flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5M7.5 7.5h9m-9 9h9"/>
                </svg>
            </span>
            <p class="mt-3 font-medium text-slate-700 dark:text-slate-200">{{ __($hasActiveFilters ? 'tickets.empty_filtered_title' : 'tickets.no_tickets') }}</p>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('tickets.empty_filtered_hint') }}</p>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                @if($hasActiveFilters)
                    <button type="button" wire:click="resetFilters" class="btn btn-secondary">{{ __('tickets.reset_filters') }}</button>
                @endif
                @can('create', App\Models\Ticket::class)
                    <a href="{{ route('tickets.create') }}" class="btn btn-primary">{{ __('tickets.new_request') }}</a>
                @endcan
            </div>
        </div>
    @else
        <div wire:loading.class.delay="hidden" class="card !p-0">
            <p class="px-3 pt-3 text-xs text-slate-500 dark:text-slate-400 sm:hidden">{{ __('tickets.scroll_hint') }}</p>
            <div class="max-h-[70vh] overflow-auto rounded-xl" tabindex="0" role="region" aria-label="{{ __('common.tickets') }}">
                <table class="table table-fixed w-full min-w-[980px]">
                    <thead class="sticky top-0 z-10 bg-white dark:bg-slate-900">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th scope="col" class="w-20 px-3 py-2 text-left">{{ __('tickets.id') }}</th>
                            <th scope="col" class="px-3 py-2 text-left">{{ __('common.title') }}</th>
                            <th scope="col" class="w-20 px-3 py-2 text-left">{{ __('common.project') }}</th>
                            <x-sortable-header field="status" :label="__('common.status')" :sort="$sort" :direction="$direction" class="w-24" />
                            <x-sortable-header field="priority" :label="__('common.priority')" :sort="$sort" :direction="$direction" class="w-24" />
                            <th scope="col" class="w-36 px-3 py-2 text-left">{{ __('common.assignee') }}</th>
                            <th scope="col" class="w-32 px-3 py-2 text-left">{{ __('tickets.reporter') }}</th>
                            <x-sortable-header field="updated_at" :label="__('common.updated_at')" :sort="$sort" :direction="$direction" class="w-24" />
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $row)
                            <tr wire:key="ticket-{{ $row->id }}"
                                tabindex="0" role="link"
                                aria-label="{{ __('tickets.open_ticket') }}: {{ $row->key }} — {{ $row->title }}"
                                x-data="{ url: '{{ route('tickets.show', $row) }}' }"
                                x-on:click="window.location = url"
                                x-on:keydown.enter.prevent="window.location = url"
                                x-on:keydown.space.prevent="window.location = url"
                                @class([
                                    'group border-t border-slate-100 transition-colors hover:bg-slate-50 focus-visible:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60 dark:focus-visible:bg-slate-800/60',
                                    'border-l-2 border-l-red-400 dark:border-l-red-600' => $row->priority->isUrgent(),
                                ])>
                                <td x-data="{ copied: false }" class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-500 dark:text-slate-400">
                                    <button type="button"
                                            x-on:keydown.stop
                                            x-on:click.stop="navigator.clipboard?.writeText(@js($row->key))?.then(() => { copied = true; setTimeout(() => copied = false, 1200) })"
                                            :title="copied ? '{{ __('tickets.copied') }}' : '{{ __('tickets.copy_id') }}'"
                                            aria-label="{{ __('tickets.copy_id') }}"
                                            class="-ml-1 inline-flex items-center gap-1 rounded px-1 py-0.5 hover:bg-slate-100 dark:hover:bg-slate-700">
                                        <span translate="no">{{ $row->key }}</span>
                                        <svg x-show="! copied" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <rect x="8" y="8" width="11" height="11" rx="2"/><path stroke-linecap="round" d="M16 5H6a2 2 0 0 0-2 2v10"/>
                                        </svg>
                                        <svg x-show="copied" class="h-3 w-3 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7.5"/>
                                        </svg>
                                        <span x-show="copied" class="sr-only" role="status">{{ __('tickets.copied') }}</span>
                                    </button>
                                </td>
                                <td class="max-w-0 px-3 py-2">
                                    <a href="{{ route('tickets.show', $row) }}" x-on:click.stop tabindex="-1"
                                       title="{{ $row->title }}"
                                       class="block truncate font-medium text-brand-700 hover:underline dark:text-brand-300">
                                        {{ $row->title }}
                                    </a>
                                    @if($row->reporter)
                                        <span class="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400 xl:hidden"
                                              title="{{ $row->reporter->name }}">{{ __('tickets.reported_by') }} {{ $row->reporter->name }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex max-w-full items-center truncate rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                                          title="{{ $row->project?->name }}">{{ $row->project?->key ?? '—' }}</span>
                                </td>
                                <td class="px-3 py-2"><x-status-badge :status="$row->status" /></td>
                                <td class="px-3 py-2"><x-priority-badge :priority="$row->priority" /></td>
                                <td class="px-3 py-2">
                                    @if($row->assignee)
                                        <div class="flex items-center gap-2">
                                            <x-avatar :name="$row->assignee->name" />
                                            <span class="truncate text-slate-700 dark:text-slate-200" title="{{ $row->assignee->name }}">{{ $row->assignee->name }}</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500">
                                            <x-avatar :name="null" />
                                            <span class="italic">{{ __('tickets.unassigned') }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($row->reporter)
                                        <span class="block truncate text-slate-600 dark:text-slate-300" title="{{ $row->reporter->name }}">{{ $row->reporter->name }}</span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-xs text-slate-500 dark:text-slate-400"
                                    title="{{ App\Support\DateFormat::dateTime($row->updated_at) }}">
                                    <time datetime="{{ $row->updated_at->toIso8601String() }}">{{ App\Support\DateFormat::format($row->updated_at) }}</time>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $tickets->links() }}
        </div>
    @endif
</div>
