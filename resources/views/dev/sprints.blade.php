@extends('layouts.app')
@section('title', __('dev.sprints'))
@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <h1 class="text-2xl font-semibold">{{ $project->name }} — {{ __('dev.sprints') }}</h1>
        @foreach($sprints as $sprint)
            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="font-semibold">{{ $sprint->name }}</h2>
                        <p class="text-xs text-slate-500">{{ $sprint->start_date->format('d M') }} – {{ $sprint->end_date->format('d M') }} · {{ $sprint->state }}</p>
                    </div>
                    @can('manageSprint', $sprint)
                        <div class="flex gap-2">
                            @if($sprint->state === 'planned')
                                <form method="POST" action="{{ route('dev.sprints.start', [$project, $sprint]) }}">@csrf
                                    <button class="btn btn-primary text-xs">{{ __('dev.start') }}</button></form>
                            @endif
                            @if($sprint->state === 'active')
                                <form method="POST" action="{{ route('dev.sprints.complete', [$project, $sprint]) }}">@csrf
                                    <button class="btn btn-secondary text-xs">{{ __('dev.complete') }}</button></form>
                            @endif
                        </div>
                    @endcan
                </div>
                <p class="mt-2 text-sm">{{ $sprint->goal }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ __('dev.remaining_points') }}: {{ $sprint->remainingPoints() }}</p>
            </div>
        @endforeach
    </div>

    @can('manageSprints', $project)
    <div class="card h-fit">
        <h2 class="mb-3 font-semibold">{{ __('dev.new_sprint') }}</h2>
        <form method="POST" action="{{ route('dev.sprints.store', $project) }}" class="space-y-3">
            @csrf
            <x-input name="name" :label="__('common.name')" required />
            <x-input name="start_date" :label="__('dev.start_date')" type="date" required />
            <x-input name="end_date" :label="__('dev.end_date')" type="date" required />
            <x-input name="capacity" :label="__('dev.capacity')" type="number" min="0" />
            <label class="block text-sm">{{ __('common.description') }}
                <textarea name="goal" rows="3" class="input mt-1 w-full"></textarea></label>
            <button class="btn btn-primary w-full" type="submit">{{ __('common.create') }}</button>
        </form>
    </div>
    @endcan
</div>
@endsection
