@extends('layouts.guest')
@section('content')
<div class="card w-full max-w-md">
    <h1 class="text-xl font-semibold">{{ __('auth.set_password') }}</h1>
    <p class="mt-2 text-sm text-slate-600">{{ $invited->email }}</p>
    <form method="POST" action="{{ $actionUrl }}" class="mt-5 space-y-4">
        @csrf
        <x-input name="password" :label="__('common.password')" type="password" autocomplete="new-password" required />
        <x-input name="password_confirmation" :label="__('common.confirm_password')" type="password" autocomplete="new-password" required />
        <button class="btn btn-primary w-full" type="submit">{{ __('common.save') }}</button>
    </form>
</div>
@endsection
