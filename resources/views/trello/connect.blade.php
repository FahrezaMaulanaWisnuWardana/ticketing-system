@extends('layouts.app')
@section('title', __('trello.connect'))
@section('content')
<div class="card max-w-2xl" x-data="{ step: 1 }">
    <h1 class="mb-5 text-xl font-semibold">{{ __('trello.connect') }} — {{ $project->name }}</h1>
    <div class="mb-5 flex gap-2 text-xs">
        @foreach([1, 2, 3, 4] as $number)
            <span class="badge" :class="step >= {{ $number }} ? 'bg-brand-100 text-brand-800 dark:bg-brand-900/50 dark:text-brand-200' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'">{{ $number }}</span>
        @endforeach
    </div>

    <form method="POST" action="{{ route('trello.connect.store', $project) }}" class="space-y-4">
        @csrf
        <div x-show="step === 1" class="space-y-3">
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('trello.step_credentials') }}</p>
            <label class="block text-sm">{{ __('trello.api_key') }}<input name="key" class="input mt-1 w-full" required></label>
            <label class="block text-sm">{{ __('trello.token') }}<input name="token" class="input mt-1 w-full" required></label>
            <label class="block text-sm">{{ __('trello.board_id') }}<input name="board_id" class="input mt-1 w-full" required></label>
            <button type="button" class="btn btn-secondary" x-on:click="step = 2">{{ __('common.back') }}</button>
        </div>

        <div x-show="step === 2" class="space-y-3">
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('trello.step_existing') }}</p>
            @foreach(['import' => __('trello.option_import'), 'push' => __('trello.option_push'), 'link' => __('trello.option_link'), 'start' => __('trello.option_start')] as $value => $label)
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="existing" value="{{ $value }}" @checked($value === 'import')> {{ $label }}</label>
            @endforeach
            <button type="button" class="btn btn-secondary" x-on:click="step = 3">{{ __('common.save') }}</button>
        </div>

        <div x-show="step === 3" class="space-y-3">
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('trello.step_mode') }}</p>
            @foreach(['import_only', 'mirror', 'two_way'] as $mode)
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="mode" value="{{ $mode }}" @checked($mode === 'import_only')> {{ $mode }}</label>
            @endforeach
            <button type="button" class="btn btn-secondary" x-on:click="step = 4">{{ __('common.save') }}</button>
        </div>

        <div x-show="step === 4" class="space-y-3">
            <p class="text-sm">{{ __('trello.step_confirm') }}</p>
            <button class="btn btn-primary" type="submit">{{ __('trello.connect') }}</button>
        </div>
    </form>
</div>
@endsection
