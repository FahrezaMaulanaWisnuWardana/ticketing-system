<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @isset($cspNonce)<meta name="csp-nonce" content="{{ $cspNonce }}">@endisset
    <meta name="reverb-key" content="{{ config('reverb.apps.apps.0.key', '') }}">
    <title>@yield('title', __('common.app_name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
<div class="min-h-screen lg:flex" x-data="{ help: false }">
    <aside class="w-full lg:w-60 shrink-0 border-b border-slate-200 bg-white lg:min-h-screen lg:border-b-0 lg:border-r p-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold text-brand-700">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">T</span>
            {{ __('common.app_name') }}
        </a>
        <nav class="mt-6 flex flex-wrap gap-1 lg:flex-col" aria-label="{{ __('common.home') }}">
            <a class="nav-link" href="{{ route('home') }}">{{ __('common.home') }}</a>
            <a class="nav-link" href="{{ route('search') }}">{{ __('common.search') }}</a>
            @if(auth()->user()?->isRequester())
                <a class="nav-link" href="{{ route('requester.requests') }}">{{ __('common.my_requests') }}</a>
            @else
                <a class="nav-link" href="{{ route('tickets.index') }}">{{ __('common.tickets') }}</a>
                <a class="nav-link" href="{{ route('agent.queue') }}">{{ __('agent.queue') }}</a>
                <a class="nav-link" href="{{ route('projects.index') }}">{{ __('common.projects') }}</a>
            @endif
            @can('viewAny', App\Models\User::class)
                <a class="nav-link" href="{{ route('admin.users.index') }}">{{ __('common.users') }}</a>
                <a class="nav-link" href="{{ route('admin.audit-logs') }}">{{ __('common.audit_log') }}</a>
                <a class="nav-link" href="{{ route('admin.settings.index') }}">{{ __('common.settings') }}</a>
                <a class="nav-link" href="{{ route('admin.settings.sla') }}">{{ __('sla.settings') }}</a>
                <a class="nav-link" href="{{ route('health') }}">{{ __('common.health') }}</a>
            @endcan
        </nav>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-primary" x-shortcut="c" x-on:click="$dispatch('open-quick-create')"
                        @can('create', App\Models\Ticket::class) @else disabled @endcan>
                    + {{ __('common.create') }}
                </button>
                <form action="{{ route('tickets.index') }}" method="GET" role="search" class="hidden sm:block">
                    <label class="sr-only" for="global-search">{{ __('common.search') }}</label>
                    <input id="global-search" x-shortcut="/" name="q" type="search" placeholder="{{ __('common.search') }}"
                           value="{{ request('q') }}" class="input w-56">
                </form>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-ghost" x-on:click="help = true" x-shortcut="?">?</button>
                <form action="{{ route('locale.switch') }}" method="POST">
                    @csrf
                    <label class="sr-only" for="locale">{{ __('common.language') }}</label>
                    <select id="locale" name="locale" class="input py-1" onchange="this.form.submit()">
                        <option value="id" @selected(app()->getLocale() === 'id')>ID</option>
                        <option value="en" @selected(app()->getLocale() === 'en')>EN</option>
                    </select>
                </form>
                <span class="text-sm text-slate-600">{{ auth()->user()?->name }}</span>
                <form action="{{ route('logout') }}" method="POST">@csrf<button class="btn btn-ghost">{{ __('common.sign_out') }}</button></form>
            </div>
        </header>

        <main class="mx-auto max-w-7xl p-4 sm:p-6">
            @if(session('status'))<div class="alert mb-4" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())
                <div class="alert alert-error mb-4" role="alert">
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <div x-show="help" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-on:keydown.escape.window="help = false">
        <div class="card max-w-md" role="dialog" aria-modal="true" aria-label="{{ __('common.shortcuts') }}">
            <h2 class="text-lg font-semibold">{{ __('common.shortcuts') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('common.keyboard_hint') }}</p>
            <button class="btn btn-secondary mt-4" x-on:click="help = false">{{ __('common.cancel') }}</button>
        </div>
    </div>
</div>

@livewire('quick-create')
@livewireScripts
@stack('scripts')
</body>
</html>
