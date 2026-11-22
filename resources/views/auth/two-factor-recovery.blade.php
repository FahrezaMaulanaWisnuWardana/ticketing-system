@extends('layouts.app')
@section('content')
<div class="card max-w-lg">
    <h1 class="text-xl font-semibold">{{ __('auth.recovery_codes') }}</h1>
    <ul class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm">@foreach($codes as $code)<li class="rounded bg-slate-100 p-2">{{ $code }}</li>@endforeach</ul>
    <a href="{{ route('home') }}" class="btn btn-primary mt-5">{{ __('common.back') }}</a>
</div>
@endsection
