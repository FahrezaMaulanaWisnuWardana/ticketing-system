@extends('layouts.app')
@section('title', __('tickets.new_request'))
@section('content')
<div class="card max-w-2xl">
    <h1 class="mb-5 text-xl font-semibold">{{ __('tickets.new_request') }}</h1>
    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div class="space-y-1">
            <label for="project_id" class="block text-sm font-medium text-slate-700">{{ __('tickets.select_project') }}</label>
            <select id="project_id" name="project_id" class="input w-full" required>
                <option value="">{{ __('tickets.select_project') }}</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }} ({{ $project->key }})</option>
                @endforeach
            </select>
            @error('project_id')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="space-y-1">
            <label for="category_id" class="block text-sm font-medium text-slate-700">{{ __('tickets.select_category') }}</label>
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
        <div class="space-y-1">
            <label for="description" class="block text-sm font-medium text-slate-700">{{ __('common.description') }}</label>
            <textarea id="description" name="description" rows="5" class="input w-full">{{ old('description') }}</textarea>
        </div>
        <div class="space-y-1">
            <label for="attachments" class="block text-sm font-medium text-slate-700">{{ __('common.attachments') }}</label>
            <input id="attachments" name="attachments[]" type="file" multiple class="input w-full">
        </div>
        <button class="btn btn-primary" type="submit">{{ __('common.send') }}</button>
    </form>
</div>
@endsection
