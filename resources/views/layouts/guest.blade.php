<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.sign_in'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="relative flex min-h-screen items-center justify-center bg-slate-100 dark:bg-slate-950 p-4">
    <form action="{{ route('theme.switch') }}" method="POST" class="absolute right-4 top-4">
        @csrf
        <input type="hidden" name="theme" value="{{ ($theme ?? 'light') === 'dark' ? 'light' : 'dark' }}">
        <button type="submit" class="btn btn-secondary px-2"
                title="{{ ($theme ?? 'light') === 'dark' ? __('common.theme_light') : __('common.theme_dark') }}"
                aria-label="{{ ($theme ?? 'light') === 'dark' ? __('common.theme_light') : __('common.theme_dark') }}">
            @if(($theme ?? 'light') === 'dark')
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            @else
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 0 1 8.646 3.646 9 9 0 1 0 20.354 15.354Z"/></svg>
            @endif
        </button>
    </form>
    @if(session('status'))<div class="alert mb-4" role="status">{{ session('status') }}</div>@endif
    @yield('content')
    @livewireScripts
</body>
</html>
