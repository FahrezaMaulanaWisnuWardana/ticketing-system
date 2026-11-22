@php
    use App\Enums\TicketStatus;

    $cards = [
        [
            'label' => __('pm.summary.completed'),
            'value' => $summary['completed'],
            'hint' => __('pm.summary.last_days', ['days' => $summary['days']]),
            'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'tone' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10',
        ],
        [
            'label' => __('pm.summary.updated'),
            'value' => $summary['updated'],
            'hint' => __('pm.summary.last_days', ['days' => $summary['days']]),
            'icon' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z',
            'tone' => 'text-indigo-600 bg-indigo-50 dark:bg-indigo-500/10',
        ],
        [
            'label' => __('pm.summary.created'),
            'value' => $summary['created'],
            'hint' => __('pm.summary.last_days', ['days' => $summary['days']]),
            'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z',
            'tone' => 'text-blue-600 bg-blue-50 dark:bg-blue-500/10',
        ],
        [
            'label' => __('pm.summary.due_soon'),
            'value' => $summary['due_soon'],
            'hint' => __('pm.summary.next_days', ['days' => $summary['days']]),
            'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
            'tone' => 'text-amber-600 bg-amber-50 dark:bg-amber-500/10',
        ],
    ];
@endphp

<section class="mt-6" aria-labelledby="project-summary-heading">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 id="project-summary-heading" class="text-lg font-semibold">{{ __('pm.summary.title') }}</h2>
        <details class="relative">
            <summary class="btn btn-secondary cursor-pointer list-none text-xs [&::-webkit-details-marker]:hidden">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M6 12h12m-9 5h6"/></svg>
                {{ __('common.filter') }}
            </summary>
            <div class="absolute right-0 z-10 mt-2 w-36 rounded-lg border border-slate-200 bg-white p-1 shadow-lg dark:border-slate-700 dark:bg-slate-800">
                <p class="px-3 py-1 text-xs font-medium text-slate-400 dark:text-slate-500">{{ __('pm.summary.period') }}</p>
                @foreach($summary['periods'] as $period)
                    <a href="{{ route('projects.overview', ['project' => $project, 'days' => $period]) }}"
                       class="block rounded-md px-3 py-2 text-sm {{ $period === $summary['days'] ? 'bg-brand-50 font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700' }}"
                       @if($period === $summary['days']) aria-current="true" @endif>
                        {{ __('pm.summary.days', ['days' => $period]) }}
                    </a>
                @endforeach
            </div>
        </details>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($cards as $card)
            <div class="card flex items-start gap-3 p-4">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $card['tone'] }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-lg font-semibold tabular">{{ $card['value'] }} <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $card['label'] }}</span></p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $card['hint'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <div class="card">
            <h3 class="font-semibold">{{ __('pm.summary.status_overview') }}</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ __('pm.summary.status_hint') }}
                <a class="text-brand-700 hover:underline dark:text-brand-300" href="{{ route('dev.list', $project) }}">{{ __('pm.summary.view_all') }}</a>
            </p>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-10 sm:justify-start">
                <div class="relative h-44 w-44 shrink-0 rounded-full" style="background: conic-gradient({{ $summary['gradient'] }})"
                     role="img" aria-label="{{ __('pm.summary.total_work_items') }}: {{ $summary['total'] }}">
                    <div class="absolute inset-[18%] flex flex-col items-center justify-center rounded-full bg-white dark:bg-slate-900">
                        <span class="text-2xl font-semibold tabular">{{ $summary['total'] }}</span>
                        <span class="px-2 text-center text-xs text-slate-500 dark:text-slate-400">{{ __('pm.summary.total_work_items') }}</span>
                    </div>
                </div>
                <ul class="space-y-2 text-sm">
                    @forelse($summary['statuses'] as $item)
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-sm" style="background: {{ $item['color'] }}"></span>
                            <span>{{ $item['label'] }}:</span>
                            <span class="tabular">{{ $item['count'] }}</span>
                        </li>
                    @empty
                        <li class="text-slate-500 dark:text-slate-400">—</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card">
            <h3 class="font-semibold">{{ __('pm.summary.recent_activity') }}</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('pm.summary.activity_hint') }}</p>
            <ul class="mt-4 max-h-80 space-y-4 overflow-y-auto pr-1 text-sm">
                @forelse($summary['activities'] as $item)
                    <li class="flex gap-3">
                        <x-avatar :name="$item['user']" size="lg" />
                        <div class="min-w-0 flex-1">
                            <p class="leading-relaxed">
                                <span class="font-medium">{{ $item['user'] }}</span>
                                {{ $item['event_label'] }}
                                @if($item['ticket_key'] !== '')
                                    <a class="text-brand-700 hover:underline dark:text-brand-300" href="{{ route('tickets.show', $item['ticket_key']) }}"><span translate="no">{{ $item['ticket_key'] }}</span>: {{ $item['ticket_title'] }}</a>
                                    @if($item['status'] instanceof TicketStatus)<x-status-badge :status="$item['status']" />@endif
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $item['time'] }}</p>
                            @if($item['comment'] !== null && $item['comment'] !== '')
                                <p class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $item['comment'] }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-slate-500 dark:text-slate-400">{{ __('pm.summary.no_activity') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</section>
