@extends('layouts.guest')
@section('content')
<div class="card w-full max-w-md">
    <h1 class="text-xl font-semibold">{{ __('common.reset_password') }}</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-input name="email" :label="__('common.email')" type="email" :value="$email" required />
        <x-input name="password" :label="__('common.password')" type="password" autocomplete="new-password" required />
        <x-input name="password_confirmation" :label="__('common.confirm_password')" type="password" autocomplete="new-password" required />
        <button class="btn btn-primary w-full" type="submit">{{ __('common.save') }}</button>
    </form>
</div>
@endsection
