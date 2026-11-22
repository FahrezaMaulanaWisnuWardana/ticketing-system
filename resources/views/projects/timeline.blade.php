@extends('layouts.app')
@section('title', __('pm.timeline'))
@section('content')
<h1 class="mb-5 text-2xl font-semibold">{{ $project->name }} — {{ __('pm.timeline') }}</h1>
<div class="card overflow-x-auto">
    <div id="gantt"></div>
</div>
@endsection
@push('scripts')
<script @isset($cspNonce) nonce="{{ $cspNonce }}" @endisset>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.Gantt) {
            new window.Gantt(document.getElementById('gantt'), @json($tasks), {
                view_mode: 'Week',
                bar_height: 24,
                on_click: function () { window.location.href = '{{ route('projects.epics', $project) }}'; },
            });
        }
    });
</script>
@endpush
