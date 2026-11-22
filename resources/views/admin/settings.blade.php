@extends('layouts.app')
@section('title', __('common.settings'))
@section('content')
<div class="card max-w-xl">
    <h1 class="mb-5 text-xl font-semibold">{{ __('common.settings') }}</h1>
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
        @csrf @method('PUT')
        <x-input name="auto_close_days" :label="__('admin.auto_close_days')" type="number" :value="$autoCloseDays" required />
        <x-input name="reopen_days" :label="__('admin.reopen_days')" type="number" :value="$reopenDays" required />
        <button class="btn btn-primary" type="submit">{{ __('common.save') }}</button>
    </form>
</div>
@endsection
