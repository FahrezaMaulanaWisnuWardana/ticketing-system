@extends('layouts.app')
@section('title', __('common.audit_log'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ __('common.audit_log') }}</h1>
<div class="card overflow-x-auto">
    <table class="table">
        <thead><tr><th>{{ __('common.created_at') }}</th><th>{{ __('common.action') }}</th><th>{{ __('common.subject') }}</th><th>{{ __('common.ip') }}</th></tr></thead>
        <tbody>@foreach($logs as $log)
            <tr class="border-t border-slate-100">
                <td>{{ App\Support\DateFormat::format($log->created_at) }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->subject_type }} {{ $log->subject_id }}</td>
                <td>{{ $log->ip }}</td>
            </tr>
        @endforeach</tbody>
    </table>
</div>
{{ $logs->links() }}
@endsection
