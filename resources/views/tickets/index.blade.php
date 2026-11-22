@extends('layouts.app')
@section('title', __('common.tickets'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ __('common.tickets') }}</h1>
@livewire('ticket-list')
@endsection
