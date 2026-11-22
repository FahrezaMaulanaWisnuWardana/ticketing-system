@extends('layouts.app')
@section('title', $project->name.' — '.__('common.tickets'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ $project->name }} — {{ __('common.tickets') }}</h1>
@livewire('dev-list', ['project' => $project->id])
@endsection
