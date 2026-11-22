@extends('layouts.app')
@section('title', __('reports.title'))
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">{{ $project->name }} — {{ __('reports.title') }}</h1>
    <div class="flex gap-2">
        @foreach(['status', 'assignee', 'overdue', 'sla'] as $type)
            <a class="btn btn-secondary text-xs" href="{{ route('reports.export', [$project, 'type' => $type]) }}">{{ __('reports.export') }} ({{ $type }})</a>
        @endforeach
    </div>
</div>

<div class="mt-6 grid gap-4 md:grid-cols-3">
    <div class="card"><h2 class="font-semibold">{{ __('reports.sla_hit_rate') }}</h2><p class="mt-2 text-2xl tabular">{{ $summary['sla_hit_rate'] }}%</p></div>
    <div class="card"><h2 class="font-semibold">{{ __('reports.avg_first_response') }}</h2><p class="mt-2 text-2xl tabular">{{ $summary['avg_first_response_minutes'] }} min</p></div>
    <div class="card"><h2 class="font-semibold">{{ __('reports.avg_resolve') }}</h2><p class="mt-2 text-2xl tabular">{{ $summary['avg_resolve_minutes'] }} min</p></div>
</div>

<div class="mt-6 grid gap-4 md:grid-cols-2">
    <div class="card">
        <h2 class="mb-3 font-semibold">{{ __('reports.by_status') }}</h2>
        <ul class="text-sm">@foreach($summary['status'] as $status => $total)
            @php $case = App\Enums\TicketStatus::tryFrom((string) $status)?->group(); @endphp
            <li class="flex justify-between"><span>{{ $case ? __($case->labelKey()) : $status }}</span><span>{{ $total }}</span></li>
        @endforeach</ul>
    </div>
    <div class="card">
        <h2 class="mb-3 font-semibold">{{ __('reports.by_assignee') }}</h2>
        <ul class="text-sm">@foreach($summary['assignee'] as $name => $total)<li class="flex justify-between"><span>{{ $name }}</span><span>{{ $total }}</span></li>@endforeach</ul>
    </div>
    <div class="card">
        <h2 class="mb-3 font-semibold">{{ __('reports.burndown') }}</h2>
        <ul class="text-xs">@foreach($burndown as $point)<li>{{ $point['date'] }} — {{ $point['remaining_points'] }} pts</li>@endforeach</ul>
    </div>
    <div class="card">
        <h2 class="mb-3 font-semibold">{{ __('reports.velocity') }}</h2>
        <ul class="text-xs">@foreach($velocity as $row)<li>{{ $row['name'] }} — {{ $row['points'] }} pts</li>@endforeach</ul>
    </div>
</div>
@endsection
