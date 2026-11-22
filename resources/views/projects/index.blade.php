@extends('layouts.app')
@section('title', __('common.projects'))
@section('content')
<div class="mb-5 flex items-center justify-between">
    <h1 class="text-2xl font-semibold">{{ __('common.projects') }}</h1>
    @can('create', App\Models\Project::class)
        <a href="{{ route('projects.create') }}" class="btn btn-primary">+ {{ __('projects.new_project') }}</a>
    @endcan
</div>

@if($projects->isEmpty())
    <x-empty-state />
@else
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($projects as $project)
        <a href="{{ route('projects.show', $project) }}" class="card hover:border-brand-300">
            <div class="flex items-center justify-between">
                <span class="badge bg-slate-100 text-slate-700">{{ $project->key }}</span>
                <x-badge :tone="$project->status->value === 'active' ? 'emerald' : 'slate'">{{ __('status.'.$project->status->value) }}</x-badge>
            </div>
            <h2 class="mt-3 font-semibold">{{ $project->name }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('projects.owner') }}: {{ $project->owner?->name }}</p>
            <p class="mt-3 text-sm text-slate-600">{{ __('projects.tickets') }}: {{ $project->tickets_count }}</p>
        </a>
    @endforeach
</div>
{{ $projects->links() }}
@endif
@endsection
