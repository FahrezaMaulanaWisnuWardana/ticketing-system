{{-- Backwards-compatible shim: every existing @extends('layouts.app') view now
     renders through the reusable <x-layout> component. --}}
@php
    $portalPageTitle = trim((string) \Illuminate\Support\Facades\View::yieldContent('title'));
@endphp
<x-layout :title="$portalPageTitle !== '' ? $portalPageTitle : null">
    @yield('content')
</x-layout>
