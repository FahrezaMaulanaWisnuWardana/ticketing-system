@extends('layouts.guest')
@section('content')
<div class="card w-full max-w-md">
    <h1 class="text-xl font-semibold">{{ __('auth.two_factor') }}</h1>
    <form method="POST" action="{{ route('2fa.verify') }}" class="mt-5 space-y-4">
        @csrf
        <x-input name="code" :label="__('auth.code')" inputmode="numeric" autocomplete="one-time-code" required />
        <button class="btn btn-primary w-full" type="submit">{{ __('common.sign_in') }}</button>
    </form>
</div>
@endsection
