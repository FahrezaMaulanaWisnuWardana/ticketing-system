@extends('layouts.app')
@section('title', $project->name)
@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold">{{ $project->name }} <span class="text-base text-slate-400 dark:text-slate-500" translate="no">{{ $project->key }}</span></h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('projects.owner') }}: {{ $project->owner?->name }}</p>
    </div>
    <div class="flex gap-2">
        <a class="btn btn-secondary" href="{{ route('projects.overview', $project) }}">{{ __('pm.overview') }}</a>
        <a class="btn btn-secondary" href="{{ route('dev.board', $project) }}">{{ __('dev.board') }}</a>
        <a class="btn btn-secondary" href="{{ route('dev.backlog', $project) }}">{{ __('dev.backlog') }}</a>
        <a class="btn btn-secondary" href="{{ route('dev.list', $project) }}">{{ __('common.tickets') }}</a>
        <a class="btn btn-secondary" href="{{ route('dev.sprints', $project) }}">{{ __('dev.sprints') }}</a>
        <a class="btn btn-secondary" href="{{ route('reports.index', $project) }}">{{ __('reports.title') }}</a>
        @can('update', $project)<a class="btn btn-secondary" href="{{ route('projects.edit', $project) }}">{{ __('common.edit') }}</a>@endcan
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <h2 class="mb-3 font-semibold">{{ __('common.tickets') }}</h2>
        @if($tickets->isEmpty())
            <x-empty-state />
        @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>{{ __('common.title') }}</th><th>{{ __('common.status') }}</th><th>{{ __('common.assignee') }}</th></tr></thead>
                <tbody>@foreach($tickets as $ticket)
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td><a class="text-brand-700 dark:text-brand-300 hover:underline" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->key }} — {{ $ticket->title }}</a></td>
                        <td><x-status-badge :status="$ticket->status" /></td>
                        <td>
                            @if($ticket->assignee)
                                <div class="flex items-center gap-2"><x-avatar :name="$ticket->assignee->name" /><span class="truncate">{{ $ticket->assignee->name }}</span></div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500">{{ __('tickets.unassigned') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach</tbody>
            </table>
        </div>
        {{ $tickets->links() }}
        @endif
    </div>

    <div class="space-y-6">
        <div class="card">
            <h2 class="mb-3 font-semibold">{{ __('projects.members') }}</h2>
            <ul class="space-y-2 text-sm">@foreach($project->members as $member)
                <li class="flex items-center justify-between">
                    <span>{{ $member->name }}</span>
                    <x-badge>{{ __('project_roles.'.$member->pivot->role?->value) }}</x-badge>
                </li>
            @endforeach</ul>
            @can('manageMembers', $project)
                <form method="POST" action="{{ route('projects.members.store', $project) }}" class="mt-4 space-y-2">
                    @csrf
                    <label class="sr-only" for="user_id">{{ __('common.users') }}</label>
                    <select id="user_id" name="user_id" class="input w-full" required>
                        @foreach(App\Models\User::where('is_active', true)->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="role">{{ __('common.role') }}</label>
                    <select id="role" name="role" class="input w-full">
                        <option value="member">{{ __('project_roles.member') }}</option>
                        <option value="lead">{{ __('project_roles.lead') }}</option>
                    </select>
                    <button class="btn btn-secondary w-full" type="submit">{{ __('projects.member_added') }}</button>
                </form>
            @endcan
        </div>

        @can('manageCategories', $project)
        <div class="card">
            <h2 class="mb-3 font-semibold">{{ __('common.category') }}</h2>
            <ul class="mb-4 space-y-1 text-sm">@foreach($project->categories as $category)<li>{{ $category->name }} <span class="text-slate-400 dark:text-slate-500">({{ $category->kind->value }})</span></li>@endforeach</ul>
            <form method="POST" action="{{ route('categories.store', $project) }}" class="space-y-2">
                @csrf
                <label class="sr-only" for="category-name">{{ __('common.category') }}</label>
                <input id="category-name" name="name" class="input w-full" placeholder="{{ __('common.category') }}" required>
                <label class="sr-only" for="category-kind">{{ __('common.tickets') }}</label>
                <select id="category-kind" name="kind" class="input w-full">
                    <option value="request">request</option><option value="dev">dev</option>
                </select>
                <button class="btn btn-secondary w-full" type="submit">{{ __('common.create') }}</button>
            </form>
        </div>
        @endcan

        @can('viewAny', App\Models\CannedReply::class)
        <div class="card">
            <h2 class="mb-3 font-semibold">{{ __('canned.title') }}</h2>
            @livewire('canned-reply-editor', ['project' => $project->id])
        </div>
        @endcan
    </div>
</div>
@endsection
