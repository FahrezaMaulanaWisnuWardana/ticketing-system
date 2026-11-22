@extends('layouts.app')
@section('content')
<div class="card max-w-xl">
    <h1 class="mb-5 text-xl font-semibold">{{ __('common.edit') }} {{ $project->key }}</h1>
    <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-4">
        @csrf @method('PUT')
        <x-input name="name" :label="__('projects.name')" :value="$project->name" required />
        <x-input name="key" :label="__('projects.key')" :value="$project->key" required />
        <div class="space-y-1">
            <label for="status" class="block text-sm font-medium text-slate-700">{{ __('common.status') }}</label>
            <select id="status" name="status" class="input w-full">
                @foreach(App\Enums\ProjectStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($project->status === $case)>{{ __('status.'.$case->value) }}</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-1">
            <label for="description" class="block text-sm font-medium text-slate-700">{{ __('common.description') }}</label>
            <textarea id="description" name="description" rows="3" class="input w-full">{{ old('description', $project->description) }}</textarea>
        </div>
        <button class="btn btn-primary" type="submit">{{ __('common.save') }}</button>
    </form>
</div>
@endsection
