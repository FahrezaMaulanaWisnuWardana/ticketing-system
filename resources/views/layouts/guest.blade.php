<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.sign_in'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    @if(session('status'))<div class="alert mb-4" role="status">{{ session('status') }}</div>@endif
    @yield('content')
    @livewireScripts
</body>
</html>
