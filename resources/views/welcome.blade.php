@extends('layouts.guest')
@section('content')
<div class="card text-center">
    <h1 class="text-2xl font-semibold">{{ __('common.app_name') }}</h1>
    <a href="{{ route('login') }}" class="btn btn-primary mt-6">{{ __('common.sign_in') }}</a>
</div>
@endsection
