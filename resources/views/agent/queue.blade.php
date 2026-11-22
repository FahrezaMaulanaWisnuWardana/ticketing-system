@extends('layouts.app')
@section('title', __('agent.queue'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ __('agent.queue') }}</h1>
@livewire('helpdesk-queue')
@endsection
