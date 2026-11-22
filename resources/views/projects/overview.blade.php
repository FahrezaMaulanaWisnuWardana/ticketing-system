@extends('layouts.app')
@section('title', $project->name)
@section('content')
<div class="flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold">{{ $project->name }} <span class="text-base text-slate-400 dark:text-slate-500">{{ $project->key }}</span></h1>
        <x-badge :tone="$metrics['health'] === 'late' ? 'rose' : ($metrics['health'] === 'at_risk' ? 'amber' : 'emerald')">
            {{ __('pm.health.'.$metrics['health']) }}
        </x-badge>
    </div>
    <div class="flex flex-wrap gap-2">
        <a class="btn btn-secondary" href="{{ route('projects.epics', $project) }}">{{ __('pm.epics') }}</a>
        <a class="btn btn-secondary" href="{{ route('projects.timeline', $project) }}">{{ __('pm.timeline') }}</a>
        <a class="btn btn-secondary" href="{{ route('projects.workload', $project) }}">{{ __('pm.workload') }}</a>
    </div>
</div>

@if(config('trello.enabled') && (auth()->user()->isAdmin() || auth()->user()->isLeadOf($project))
    && ! in_array($project->trello_prompt->value, ['connected', 'no'], true)
    && $project->trello_prompt_at?->lt(now()->subDays(7))
    && (int) App\Models\Setting::get('trello.banner_dismissed.'.$project->id, 0) < 2)
<div class="alert mb-4 flex items-center justify-between" role="status">
    <span>{{ __('trello.banner') }}</span>
    <span class="flex gap-2">
        <a class="btn btn-primary text-xs" href="{{ route('trello.connect', $project) }}">{{ __('trello.connect') }}</a>
        <form method="POST" action="{{ route('trello.banner.dismiss', $project) }}">@csrf
            <button class="btn btn-ghost text-xs">{{ __('common.cancel') }}</button></form>
    </span>
</div>
@endif

@if(config('trello.enabled'))
<div class="mb-4"><a class="btn btn-secondary text-xs" href="{{ route('trello.settings', $project) }}">{{ __('trello.settings') }}</a></div>
@endif

@include('projects.partials.summary', ['project' => $project, 'summary' => $summary])

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="card">
        <h2 class="font-semibold">{{ __('pm.progress') }}</h2>
        <div class="mt-3 h-3 w-full rounded-full bg-slate-100 dark:bg-slate-800">
            <div class="h-3 rounded-full bg-brand-500" style="width: {{ $metrics['progress'] }}%"></div>
        </div>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $metrics['progress'] }}% ({{ $metrics['done'] }}/{{ $metrics['total'] }})</p>
    </div>
    <div class="card">
        <h2 class="font-semibold">{{ __('pm.milestones') }}</h2>
        <ul class="mt-3 space-y-2 text-sm">@forelse($milestones as $milestone)
            <li class="flex justify-between"><span>{{ $milestone->name }}</span><span class="text-slate-500 dark:text-slate-400">{{ App\Support\DateFormat::date($milestone->due_date) }} · {{ $milestone->state }}</span></li>
        @empty<li class="text-slate-500 dark:text-slate-400">—</li>@endforelse</ul>
    </div>
    <div class="card">
        <h2 class="font-semibold">{{ __('pm.workload') }}</h2>
        <ul class="mt-3 space-y-2 text-sm">@forelse($workload as $row)
            <li class="flex justify-between"><span>{{ $row['name'] }}</span><span class="text-slate-500 dark:text-slate-400">{{ $row['count'] }} · {{ $row['points'] }} pts</span></li>
        @empty<li class="text-slate-500 dark:text-slate-400">—</li>@endforelse</ul>
    </div>
</div>

@can('manageSprints', $project)
<div class="card mt-6">
    <h2 class="mb-3 font-semibold">{{ __('pm.new_milestone') }}</h2>
    <form method="POST" action="{{ route('projects.milestones.store', $project) }}" class="flex flex-wrap gap-2">
        @csrf
        <input name="name" class="input flex-1" placeholder="{{ __('common.name') }}" required>
        <input type="date" name="due_date" class="input" required>
        <button class="btn btn-secondary" type="submit">{{ __('common.create') }}</button>
    </form>
</div>
@endcan
@endsection
