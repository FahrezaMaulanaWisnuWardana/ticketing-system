@extends('layouts.app')
@section('title', __('tickets.new_request'))
@section('content')
@php
    $preferredKey = $type ? data_get(App\Support\RequestTypeCatalog::find($type), 'project') : null;
    $preferredProject = $preferredKey ? $projects->firstWhere('key', $preferredKey) : null;
@endphp
<div class="card max-w-2xl">
    <h1 class="mb-1 text-xl font-semibold">{{ __('tickets.new_request') }}</h1>
    @if($typeLabel)
        <p class="mb-5 text-sm text-slate-600 dark:text-slate-400">{{ __('portal.list.type') }}: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $typeLabel }}</span></p>
    @else
        <div class="mb-5"></div>
    @endif
    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="type" value="{{ old('type', $type ?? 'task') }}">
        <div class="space-y-1">
            <label for="project_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('tickets.select_project') }}</label>
            <select id="project_id" name="project_id" class="input w-full" required>
                <option value="">{{ __('tickets.select_project') }}</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" @selected(old('project_id', $preferredProject?->id) == $project->id)>{{ $project->name }} ({{ $project->key }})</option>
                @endforeach
            </select>
            @error('project_id')<p class="text-xs text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="space-y-1">
            <label for="category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('tickets.select_category') }}</label>
            <select id="category_id" name="category_id" class="input w-full">
                <option value="">{{ __('tickets.select_category') }}</option>
                @foreach($projects as $project)
                    @foreach($project->categories as $category)
                        <option value="{{ $category->id }}" data-project="{{ $project->id }}" @selected(old('category_id') == $category->id)>{{ $project->key }} — {{ $category->name }}</option>
                    @endforeach
                @endforeach
            </select>
        </div>
        <input type="hidden" name="kind" value="request">
        <x-input name="title" :label="__('common.title')" maxlength="200" required />
        <x-rich-editor name="description" id="description" :label="__('common.description')"
                       :value="old('description', '')" :placeholder="__('tickets.description_placeholder')" />
        <div class="space-y-1">
            <label for="attachments" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('common.attachments') }}</label>
            <input id="attachments" name="attachments[]" type="file" multiple class="input w-full">
        </div>
        <button class="btn btn-primary" type="submit">{{ __('common.send') }}</button>
    </form>
</div>
@endsection
