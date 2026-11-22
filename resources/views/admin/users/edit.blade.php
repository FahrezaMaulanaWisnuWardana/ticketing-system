@extends('layouts.app')
@section('content')
<div class="card max-w-xl">
    <h1 class="mb-5 text-xl font-semibold">{{ $subject->name }}</h1>
    <form method="POST" action="{{ route('admin.users.update', $subject) }}" class="space-y-4">
        @csrf @method('PUT')
        <x-input name="name" :label="__('common.name')" :value="$subject->name" required />
        <div class="space-y-1">
            <label for="role" class="block text-sm font-medium text-slate-700">{{ __('common.role') }}</label>
            <select id="role" name="role" class="input w-full">
                @foreach(App\Enums\Role::cases() as $case)<option value="{{ $case->value }}" @selected($subject->role === $case)>{{ __('roles.'.$case->value) }}</option>@endforeach
            </select>
        </div>
        <div class="space-y-1">
            <label for="timezone" class="block text-sm font-medium text-slate-700">{{ __('common.timezone') }}</label>
            <input id="timezone" name="timezone" class="input w-full" value="{{ $subject->timezone }}">
        </div>
        <button class="btn btn-primary" type="submit">{{ __('common.save') }}</button>
    </form>
    <form method="POST" action="{{ route('admin.users.2fa.reset', $subject) }}" class="mt-4">@csrf
        <button class="btn btn-secondary" type="submit">{{ __('admin.reset_2fa') }}</button>
    </form>
</div>
@endsection
