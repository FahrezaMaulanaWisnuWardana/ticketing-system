@extends('layouts.guest')

@section('content')
<div class="card w-full max-w-md">
    <h1 class="text-2xl font-semibold">{{ __('common.sign_in') }}</h1>
    <a href="{{ route('auth.google') }}" class="btn btn-secondary mt-6 w-full">{{ __('common.sign_in_google') }}</a>
    <div class="my-5 flex items-center gap-3 text-xs text-slate-400"><span class="h-px flex-1 bg-slate-200"></span>{{ __('common.or') }}<span class="h-px flex-1 bg-slate-200"></span></div>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-input name="email" :label="__('common.email')" type="email" autocomplete="username" required />
        <x-input name="password" :label="__('common.password')" type="password" autocomplete="current-password" required />
        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button class="btn btn-primary w-full" type="submit">{{ __('common.sign_in') }}</button>
    </form>
    <a class="mt-4 inline-block text-sm text-brand-600" href="{{ route('password.request') }}">{{ __('common.forgot_password') }}</a>
</div>
@endsection
