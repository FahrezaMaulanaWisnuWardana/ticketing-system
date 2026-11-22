@props([
    'tickets',
    'variant' => 'requester',
    'sort' => 'created_at',
    'direction' => 'desc',
    'routeName' => 'requester.requests',
    'queryParams' => [],
    'selectable' => false,
])
@php
    use App\Enums\StatusCategory;
    use App\Services\SlaService;
    use App\Support\DateFormat;
    use App\Support\RequestTypeCatalog;

    $viewer = auth()->user();
    $agent = $variant === 'agent';
    $sortUrl = static function (string $field) use ($routeName, $queryParams, $sort, $direction): string {
        $next = $sort === $field && $direction === 'desc' ? 'asc' : 'desc';

        return route($routeName, array_merge($queryParams, ['sort' => $field, 'dir' => $next]));
    };
    $sortState = static fn (string $field): string => $sort === $field
        ? ($direction === 'asc' ? 'ascending' : 'descending')
        : 'none';
    $typeIcon = static function (?string $key): string {
        if ($key !== null && RequestTypeCatalog::isValid($key)) {
            return RequestTypeCatalog::iconPath($key);
        }

        return '<rect x="4" y="4.5" width="16" height="11" rx="2"/><path stroke-linecap="round" d="M8 19h8"/>';
    };
    $sla = app(SlaService::class);
@endphp

{{-- Requester list: mobile cards -------------------------------------- --}}
@unless($agent)
<div class="space-y-2 md:hidden">
    @forelse($tickets as $ticket)
        <div class="card !p-3">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $typeIcon($ticket->type) !!}</svg>
                </span>
                <div class="min-w-0 flex-1">
                    <a href="{{ route('requester.requests.show', $ticket) }}" class="block truncate font-medium text-slate-900 hover:text-brand-600 dark:text-slate-100 dark:hover:text-brand-400" title="{{ $ticket->title }}">{{ $ticket->title }}</a>
                    <span translate="no" class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $ticket->key }}</span>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-status-badge :status="$ticket->status" />
                @if($viewer !== null && $ticket->needsReplyFor($viewer))
                    <x-badge tone="amber" class="gap-1">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                        {{ __('portal.table.pending_reply') }}
                    </x-badge>
                @endif
                <time class="ml-auto text-xs text-slate-500 dark:text-slate-400" title="{{ DateFormat::dateTime($ticket->created_at) }}" datetime="{{ $ticket->created_at?->toIso8601String() }}">{{ DateFormat::format($ticket->created_at) }}</time>
            </div>
        </div>
    @empty
        <x-empty-state />
    @endforelse
</div>
@endunless

{{-- Desktop table ----------------------------------------------------- --}}
<div class="card !p-0 @unless($agent) hidden md:block @endunless">
    @if($agent)
        <p class="px-3 pt-3 text-xs text-slate-500 dark:text-slate-400 sm:hidden">{{ __('portal.table.scroll_hint') }}</p>
    @endif
    <div class="max-w-full overflow-x-auto" tabindex="0" role="region" aria-label="{{ __('common.tickets') }}">
        <table class="table min-w-full {{ $agent ? 'min-w-[1080px]' : '' }}">
            <thead class="bg-slate-50/70 dark:bg-slate-800/40">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    @if($selectable)
                        <th scope="col" class="w-10 px-3 py-2 text-left">
                            <input type="checkbox" x-model="all"
                                   x-on:change="$root.querySelectorAll('input[name=\'tickets[]\']').forEach(function (el) { el.checked = all })"
                                   aria-label="{{ __('portal.table.select_all') }}" class="rounded border-slate-300 dark:border-slate-600">
                        </th>
                    @endif
                    <th scope="col" class="w-12 px-3 py-2 text-left"><span class="sr-only">{{ __('portal.table.type') }}</span></th>
                    <th scope="col" class="w-28 whitespace-nowrap px-3 py-2 text-left">{{ __('portal.table.reference') }}</th>
                    <th scope="col" aria-sort="{{ $sortState('summary') }}" class="px-3 py-2 text-left">
                        <a href="{{ $sortUrl('summary') }}" class="inline-flex items-center gap-1 rounded hover:text-brand-600 dark:hover:text-brand-400" title="{{ __('portal.table.sort', ['column' => __('portal.table.summary')]) }}">
                            {{ __('portal.table.summary') }}
                            <svg class="h-3.5 w-3.5 {{ $sort === 'summary' ? 'text-brand-600 dark:text-brand-400' : 'text-slate-400 opacity-60' }} {{ $sort === 'summary' && $direction === 'asc' ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </a>
                    </th>
                    @if($agent)
                        <th scope="col" class="w-32 whitespace-nowrap px-3 py-2 text-left">{{ __('portal.table.reporter') }}</th>
                        <th scope="col" class="w-40 whitespace-nowrap px-3 py-2 text-left">{{ __('portal.table.assignee') }}</th>
                    @else
                        <th scope="col" class="w-36 whitespace-nowrap px-3 py-2 text-left">{{ __('portal.table.requester') }}</th>
                    @endif
                    <th scope="col" aria-sort="{{ $sortState('status') }}" class="w-36 whitespace-nowrap px-3 py-2 text-left">
                        <a href="{{ $sortUrl('status') }}" class="inline-flex items-center gap-1 rounded hover:text-brand-600 dark:hover:text-brand-400" title="{{ __('portal.table.sort', ['column' => __('common.status')]) }}">
                            {{ __('common.status') }}
                            <svg class="h-3.5 w-3.5 {{ $sort === 'status' ? 'text-brand-600 dark:text-brand-400' : 'text-slate-400 opacity-60' }} {{ $sort === 'status' && $direction === 'asc' ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </a>
                    </th>
                    @if($agent)
                        <th scope="col" class="w-20 whitespace-nowrap px-3 py-2 text-left"><span class="sr-only">{{ __('common.priority') }}</span></th>
                    @endif
                    <th scope="col" aria-sort="{{ $sortState('created_at') }}" class="w-32 whitespace-nowrap px-3 py-2 text-left">
                        <a href="{{ $sortUrl('created_at') }}" class="inline-flex items-center gap-1 rounded hover:text-brand-600 dark:hover:text-brand-400" title="{{ __('portal.table.sort', ['column' => __('portal.table.created')]) }}">
                            {{ __('portal.table.created') }}
                            <svg class="h-3.5 w-3.5 {{ $sort === 'created_at' ? 'text-brand-600 dark:text-brand-400' : 'text-slate-400 opacity-60' }} {{ $sort === 'created_at' && $direction === 'asc' ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </a>
                    </th>
                    @if($agent)
                        <th scope="col" class="w-32 whitespace-nowrap px-3 py-2 text-left">{{ __('portal.table.updated') }}</th>
                        <th scope="col" class="w-32 whitespace-nowrap px-3 py-2 text-left">{{ __('portal.table.due') }}</th>
                        <th scope="col" class="w-36 whitespace-nowrap px-3 py-2 text-left">{{ __('portal.table.resolution') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                        @if($selectable)
                            <td class="px-3 py-2">
                                <input type="checkbox" name="tickets[]" value="{{ $ticket->id }}" data-ticket-checkbox
                                       aria-label="{{ __('portal.table.select') }}: {{ $ticket->key }}"
                                       class="rounded border-slate-300 dark:border-slate-600">
                            </td>
                        @endif
                        <td class="px-3 py-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300" title="{{ $ticket->type }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $typeIcon($ticket->type) !!}</svg>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-500 dark:text-slate-400" translate="no">{{ $ticket->key }}</td>
                        <td class="max-w-0 px-3 py-2">
                            <a href="{{ $agent ? route('tickets.show', $ticket) : route('requester.requests.show', $ticket) }}"
                               class="block break-words font-medium text-slate-900 hover:text-brand-600 dark:text-slate-100 dark:hover:text-brand-400"
                               title="{{ $ticket->title }}">{{ $ticket->title }}</a>
                            @if(! $agent && $viewer !== null && $ticket->needsReplyFor($viewer))
                                <x-badge tone="amber" class="mt-1 gap-1">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                                    {{ __('portal.table.pending_reply') }}
                                </x-badge>
                            @endif
                        </td>
                        @if($agent)
                            <td class="px-3 py-2 text-sm text-slate-600 dark:text-slate-300">
                                <span class="block truncate" title="{{ $ticket->reporter?->name }}">{{ $ticket->reporter?->name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2">
                                @if($ticket->assignee)
                                    <span class="flex items-center gap-2"><x-avatar :name="$ticket->assignee->name" /><span class="truncate text-sm text-slate-700 dark:text-slate-200" title="{{ $ticket->assignee->name }}">{{ $ticket->assignee->name }}</span></span>
                                @else
                                    <span class="text-sm italic text-slate-400 dark:text-slate-500">{{ __('portal.table.unassigned') }}</span>
                                @endif
                            </td>
                        @else
                            <td class="px-3 py-2 text-sm text-slate-600 dark:text-slate-300">
                                <span class="block truncate" title="{{ $ticket->requester?->name ?? $ticket->reporter?->name }}">{{ $ticket->requester?->name ?? $ticket->reporter?->name ?? '—' }}</span>
                            </td>
                        @endif
                        <td class="px-3 py-2"><x-status-badge :status="$ticket->status" /></td>
                        @if($agent)
                            <td class="px-3 py-2"><x-priority-icon :priority="$ticket->priority" /></td>
                        @endif
                        <td class="whitespace-nowrap px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
                            <time title="{{ DateFormat::dateTime($ticket->created_at) }}" datetime="{{ $ticket->created_at?->toIso8601String() }}">{{ DateFormat::format($ticket->created_at) }}</time>
                        </td>
                        @if($agent)
                            @php
                                $due = $ticket->first_response_at === null ? $ticket->sla_response_due_at : $ticket->sla_resolve_due_at;
                                $slaState = $sla->status($ticket);
                                $slaTone = match ($slaState) {
                                    'breached' => 'text-red-600 dark:text-red-400',
                                    'at_risk' => 'text-amber-600 dark:text-amber-400',
                                    'met' => 'text-emerald-600 dark:text-emerald-400',
                                    default => 'text-slate-500 dark:text-slate-400',
                                };
                            @endphp
                            <td class="whitespace-nowrap px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
                                <time title="{{ DateFormat::dateTime($ticket->updated_at) }}" datetime="{{ $ticket->updated_at?->toIso8601String() }}">{{ DateFormat::format($ticket->updated_at) }}</time>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-xs text-slate-500 dark:text-slate-400" title="{{ DateFormat::dateTime($due) }}">
                                {{ $due === null ? '—' : DateFormat::format($due) }}
                            </td>
                            <td @class(['whitespace-nowrap px-3 py-2 text-xs font-medium', $slaTone]) title="{{ DateFormat::dateTime($due) }}">
                                <span class="inline-flex items-center gap-1">
                                    @if($slaState === 'breached')
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v4.5m0 3h.01"/></svg>
                                    @elseif($slaState === 'at_risk')
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7.5V12l3 1.75"/></svg>
                                    @elseif($slaState === 'met')
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 12.25 2.5 2.5 5-5"/></svg>
                                    @endif
                                    {{ __('portal.sla.'.$slaState) }}
                                </span>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $agent ? 11 : 6 }}" class="px-3 py-6 text-center">
                            <x-empty-state :title="__('portal.empty.filtered_title')" :description="__('portal.empty.filtered_hint')" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
