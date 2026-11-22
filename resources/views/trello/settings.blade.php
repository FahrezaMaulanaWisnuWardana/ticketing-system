@extends('layouts.app')
@section('title', __('trello.settings'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ $project->name }} — {{ __('trello.settings') }}</h1>

@if($connection === null)
    <div class="card max-w-xl">
        <p class="mb-4">{{ __('trello.not_connected') }}</p>
        <a href="{{ route('trello.connect', $project) }}" class="btn btn-primary">{{ __('trello.connect') }}</a>
    </div>
@else
    <div class="card">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-semibold">{{ $connection->trello_board_name ?? $connection->trello_board_id }}</h2>
                <p class="text-sm text-slate-600">{{ __('trello.status') }}: {{ $connection->status->value }} · {{ __('trello.mode') }}: {{ $connection->mode->value }} · {{ __('trello.token') }}: ••••{{ $connection->lastFour() }}</p>
                <p class="text-xs text-slate-500">{{ __('trello.last_sync') }}: {{ App\Support\DateFormat::format($connection->last_synced_at) }}</p>
                @if($connection->last_error)<p class="mt-2 text-sm text-rose-600">{{ $connection->last_error }}</p>@endif
            </div>
            <div class="flex flex-wrap gap-2">
                @if($connection->status->value === 'paused')
                    <form method="POST" action="{{ route('trello.resume', $connection) }}">@csrf<button class="btn btn-primary text-xs">{{ __('trello.resume') }}</button></form>
                @else
                    <form method="POST" action="{{ route('trello.pause', $connection) }}">@csrf<button class="btn btn-secondary text-xs">{{ __('trello.pause') }}</button></form>
                @endif
                <form method="POST" action="{{ route('trello.import', $connection) }}">@csrf
                    <input type="hidden" name="update_existing" value="1">
                    <button class="btn btn-secondary text-xs">{{ __('trello.import') }}</button>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('trello.update', [$project, $connection]) }}" class="mt-5 flex flex-wrap items-end gap-3">
            @csrf @method('PUT')
            <label class="text-sm">{{ __('trello.mode') }}
                <select name="mode" class="input mt-1">
                    <option value="import_only" @selected($connection->mode->value === 'import_only')>import_only</option>
                    <option value="mirror" @selected($connection->mode->value === 'mirror')>mirror</option>
                    <option value="two_way" @selected($connection->mode->value === 'two_way')>two_way</option>
                </select>
            </label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="mirror_helpdesk" value="1" @checked($connection->mirror_helpdesk)> {{ __('trello.mirror_helpdesk') }}</label>
            <button class="btn btn-secondary" type="submit">{{ __('common.save') }}</button>
        </form>

        <form method="POST" action="{{ route('trello.disconnect', $connection) }}" class="mt-4" onsubmit="return confirm('{{ __('trello.disconnect_confirm') }}')">
            @csrf @method('DELETE')
            <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="forget_links" value="1"> {{ __('trello.forget_links') }}</label>
            <button class="btn btn-ghost text-rose-600" type="submit">{{ __('trello.disconnect') }}</button>
        </form>
    </div>

    <div class="card mt-6">
        <h2 class="mb-3 font-semibold">{{ __('trello.imports') }}</h2>
        <ul class="text-sm">@foreach($imports as $run)
            <li><a class="text-brand-600 underline" href="{{ route('trello.import.report', $run) }}">#{{ $run->id }} — {{ $run->status }}</a> · {{ App\Support\DateFormat::format($run->finished_at) }}</li>
        @endforeach</ul>
    </div>

    <div class="card mt-6">
        <h2 class="mb-3 font-semibold">{{ __('trello.link_existing') }}</h2>
        <form method="POST" action="{{ route('trello.link', $connection) }}" class="space-y-2">
            @csrf
            @foreach($project->tickets()->limit(20)->get() as $ticket)
                <div class="flex items-center gap-2 text-sm">
                    <label class="sr-only" for="pair-{{ $ticket->id }}">{{ $ticket->title }}</label>
                    <span class="flex-1">{{ $ticket->title }}</span>
                    <input id="pair-{{ $ticket->id }}" name="pairs[{{ $loop->index }}][external_id]" class="input w-48" placeholder="Trello card id">
                    <input type="hidden" name="pairs[{{ $loop->index }}][ticket_id]" value="{{ $ticket->id }}">
                </div>
            @endforeach
            <button class="btn btn-secondary" type="submit">{{ __('trello.link') }}</button>
        </form>
    </div>

    @if($conflicts->isNotEmpty())
    <div class="card mt-6">
        <h2 class="mb-3 font-semibold">{{ __('trello.conflicts') }}</h2>
        <ul class="text-sm">@foreach($conflicts as $conflict)
            <li>{{ $conflict->field }} — {{ __('trello.app_wins') }}: {{ $conflict->app_value }} / Trello: {{ $conflict->trello_value }}</li>
        @endforeach</ul>
    </div>
    @endif
@endif
@endsection
