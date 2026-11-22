@extends('layouts.app')
@section('title', __('projects.new_project'))
@section('content')
<div class="card max-w-xl">
    <h1 class="mb-5 text-xl font-semibold">{{ __('projects.new_project') }}</h1>
    <form method="POST" action="{{ route('projects.store') }}" class="space-y-4">
        @csrf
        <x-input name="name" :label="__('projects.name')" required />
        <x-input name="key" :label="__('projects.key')" required maxlength="10" />
        <div class="space-y-1">
            <label for="template" class="block text-sm font-medium text-slate-700">{{ __('projects.template') }}</label>
            <select id="template" name="template" class="input w-full">
                <option value="dev">{{ __('projects.dev') }}</option>
                <option value="helpdesk">{{ __('projects.helpdesk') }}</option>
                <option value="blank">{{ __('projects.blank') }}</option>
            </select>
        </div>
        <div class="space-y-1">
            <label for="description" class="block text-sm font-medium text-slate-700">{{ __('common.description') }}</label>
            <textarea id="description" name="description" rows="3" class="input w-full">{{ old('description') }}</textarea>
        </div>
        @if(config('trello.enabled'))
        <fieldset class="rounded-lg border p-3">
            <legend class="text-sm font-medium">{{ __('trello.question') }}</legend>
            @foreach(['no' => __('trello.answer_no'), 'yes' => __('trello.answer_yes'), 'later' => __('trello.answer_later')] as $value => $label)
                <label class="mr-4 text-sm"><input type="radio" name="trello_choice" value="{{ $value }}" @checked($value === 'later')> {{ $label }}</label>
            @endforeach
        </fieldset>
        @endif
        <button class="btn btn-primary" type="submit">{{ __('common.create') }}</button>
    </form>
</div>
@endsection
