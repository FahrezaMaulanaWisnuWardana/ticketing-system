@extends('layouts.app')
@section('title', __('pm.epics'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ $project->name }} — {{ __('pm.epics') }}</h1>
<div class="grid gap-4 md:grid-cols-2">
    @foreach($epics as $epic)
        @php $progress = App\Domain\ProgressCalculator::percent($epic->tickets()->whereIn('status', App\Enums\TicketStatus::Done->groupValues())->count(), $epic->tickets_count); @endphp
        <div class="card">
            <h2 class="font-semibold">{{ $epic->key }} — {{ $epic->name }}</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 tabular">{{ trans_choice('common.tickets_count', $epic->tickets_count) }}</p>
            <div class="mt-3 h-2 rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-2 rounded-full bg-brand-500" style="width: {{ $progress }}%"></div></div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $progress }}%</p>
        </div>
    @endforeach
</div>

@can('manageSprints', $project)
<div class="card mt-6 max-w-xl">
    <h2 class="mb-3 font-semibold">{{ __('pm.new_epic') }}</h2>
    <form method="POST" action="{{ route('projects.epics.store', $project) }}" class="space-y-3">
        @csrf
        <input name="key" class="input w-full" placeholder="KEY" required>
        <input name="name" class="input w-full" placeholder="{{ __('common.name') }}" required>
        <div class="grid grid-cols-2 gap-2">
            <input type="date" name="start_date" class="input">
            <input type="date" name="due_date" class="input">
        </div>
        <button class="btn btn-secondary" type="submit">{{ __('common.create') }}</button>
    </form>
</div>
@endcan
@endsection
