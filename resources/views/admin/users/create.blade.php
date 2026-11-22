@extends('layouts.app')
@section('content')
<div class="card max-w-xl">
    <h1 class="mb-5 text-xl font-semibold">{{ __('admin.invite') }}</h1>
    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
        @csrf
        <x-input name="name" :label="__('common.name')" required />
        <x-input name="email" :label="__('common.email')" type="email" required />
        <div class="space-y-1">
            <label for="role" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('common.role') }}</label>
            <select id="role" name="role" class="input w-full">
                @foreach(App\Enums\Role::cases() as $case)<option value="{{ $case->value }}">{{ __('roles.'.$case->value) }}</option>@endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">{{ __('admin.invite') }}</button>
    </form>
</div>
@endsection
