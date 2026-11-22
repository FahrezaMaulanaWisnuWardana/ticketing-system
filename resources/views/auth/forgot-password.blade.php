@extends('layouts.guest')
@section('content')
<div class="card w-full max-w-md">
    <h1 class="text-xl font-semibold">{{ __('common.reset_password') }}</h1>
    <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-4">
        @csrf
        <x-input name="email" :label="__('common.email')" type="email" required />
        <button class="btn btn-primary w-full" type="submit">{{ __('common.send') }}</button>
    </form>
</div>
@endsection
