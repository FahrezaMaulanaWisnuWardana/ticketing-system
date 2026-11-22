@extends('layouts.app')
@section('title', $project->name.' — '.__('dev.backlog'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ $project->name }} — {{ __('dev.backlog') }}</h1>
@livewire('backlog', ['project' => $project->id])
@endsection
