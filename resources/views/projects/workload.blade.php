@extends('layouts.app')
@section('title', __('pm.workload'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ $project->name }} — {{ __('pm.workload') }}</h1>
<div class="card max-w-xl">
    <table class="table">
        <thead><tr><th>{{ __('common.name') }}</th><th>{{ __('common.tickets') }}</th><th>{{ __('pm.points') }}</th></tr></thead>
        <tbody>@forelse($workload as $row)
            <tr class="border-t border-slate-100"><td>{{ $row['name'] }}</td><td class="tabular">{{ $row['count'] }}</td><td class="tabular">{{ $row['points'] }}</td></tr>
        @empty<tr><td colspan="3">{{ __('common.empty_hint') }}</td></tr>@endforelse</tbody>
    </table>
</div>
@endsection
