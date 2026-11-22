@extends('layouts.app')
@section('title', __('common.search'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ __('common.search') }}</h1>
@livewire('global-search')
@endsection
