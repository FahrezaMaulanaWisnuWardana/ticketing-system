@extends('layouts.app')
@section('title', __('trello.import_report'))
@section('content')
<div class="card">
    <h1 class="text-xl font-semibold">{{ __('trello.import_report') }} #{{ $run->id }}</h1>
    <p class="mt-2">{{ __('trello.status') }}: {{ $run->status }}</p>
    <ul class="mt-3 text-sm">
        @foreach((array) $run->counts as $key => $value)<li>{{ $key }}: {{ $value }}</li>@endforeach
    </ul>
    @if($run->errors)
        <h2 class="mt-4 font-semibold">{{ __('trello.errors') }}</h2>
        <ul class="text-sm text-rose-700">@foreach($run->errors as $error)<li>{{ $error['card'] ?? '' }} — {{ $error['message'] ?? '' }}</li>@endforeach</ul>
    @endif
    <a class="btn btn-secondary mt-5" href="{{ route('trello.settings', $run->connection->project_id) }}">{{ __('common.back') }}</a>
</div>
@endsection
