@extends('layouts.app')
@section('title', $project->name.' — '.__('dev.board'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ $project->name }} — {{ __('dev.board') }}</h1>
@livewire('board', ['project' => $project->id])
@endsection
