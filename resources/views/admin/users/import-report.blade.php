@extends('layouts.app')
@section('content')
<div class="card">
    <h1 class="text-xl font-semibold">{{ __('admin.import_report') }}</h1>
    <p class="mt-3">{{ __('common.created') }}: {{ count($report['created']) }}</p>
    <p>{{ __('common.actions') }}: {{ count($report['errors']) }}</p>
    @if($report['errors'])
        <ul class="mt-4 space-y-1 text-sm text-rose-700 dark:text-rose-300">
            @foreach($report['errors'] as $error)<li>Row {{ $error['row'] }} ({{ $error['email'] }}): {{ $error['message'] }}</li>@endforeach
        </ul>
    @endif
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary mt-5">{{ __('common.back') }}</a>
</div>
@endsection
