@extends('layouts.guest')
@section('content')
<div class="card w-full max-w-md">
    <h1 class="text-xl font-semibold">{{ __('auth.set_up_2fa') }}</h1>
    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('auth.scan_secret') }}</p>
    <code class="mt-4 block break-all rounded bg-slate-100 dark:bg-slate-800 p-3 text-xs">{{ $secret }}</code>
    <p class="mt-2 break-all text-xs text-slate-500 dark:text-slate-400">otpauth: {{ $qrUrl }}</p>
    <form method="POST" action="{{ route('2fa.enable') }}" class="mt-5 space-y-4">
        @csrf
        <x-input name="code" :label="__('auth.code')" inputmode="numeric" autocomplete="one-time-code" required />
        <button class="btn btn-primary w-full" type="submit">{{ __('common.save') }}</button>
    </form>
</div>
@endsection
