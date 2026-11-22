@extends('layouts.app')
@section('title', __('common.home'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ auth()->user()->isRequester() ? __('common.my_requests') : __('common.my_work') }}</h1>

@if($tickets->isEmpty())
    <x-empty-state />
@else
<div class="card overflow-x-auto">
    <table class="table">
        <thead><tr>
            <th>{{ __('common.title') }}</th><th>{{ __('common.project') }}</th>
            <th>{{ __('common.status') }}</th><th>{{ __('common.assignee') }}</th><th>{{ __('tickets.reporter') }}</th><th>{{ __('common.created_at') }}</th>
        </tr></thead>
        <tbody>
        @foreach($tickets as $ticket)
            <tr class="border-t border-slate-100 dark:border-slate-800">
                <td><a class="font-medium text-brand-700 dark:text-brand-300 hover:underline" href="{{ route('tickets.show', $ticket) }}"><span translate="no">{{ $ticket->key }}</span> — {{ $ticket->title }}</a></td>
                <td>{{ $ticket->project->key }}</td>
                <td><x-status-badge :status="$ticket->status" :requester="auth()->user()->isRequester()" /></td>
                <td>
                    @if($ticket->assignee)
                        <div class="flex items-center gap-2"><x-avatar :name="$ticket->assignee->name" /><span class="truncate">{{ $ticket->assignee->name }}</span></div>
                    @else
                        <span class="text-slate-400 dark:text-slate-500">{{ __('tickets.unassigned') }}</span>
                    @endif
                </td>
                <td class="whitespace-nowrap">
                    @if($ticket->reporter)
                        <span class="truncate" title="{{ $ticket->reporter->name }}">{{ $ticket->reporter->name }}</span>
                    @else
                        <span class="text-slate-400 dark:text-slate-500">—</span>
                    @endif
                </td>
                <td>{{ App\Support\DateFormat::format($ticket->created_at) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $tickets->links() }}
@endif
@endsection
