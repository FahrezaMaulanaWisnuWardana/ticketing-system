<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @isset($cspNonce)<meta name="csp-nonce" content="{{ $cspNonce }}">@endisset
    <meta name="reverb-key" content="{{ config('reverb.apps.apps.0.key', '') }}">
    <meta name="echo-enabled" content="{{ config('broadcasting.default') === 'reverb' ? 'true' : 'false' }}">
    <title>@yield('title', __('common.app_name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen">
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white dark:focus:bg-slate-900 focus:px-4 focus:py-2 focus:shadow">{{ __('common.skip_to_content') }}</a>
<div class="min-h-screen lg:flex" x-data="{ help: false, notification: '' }"
     x-on:notify.window="notification = $event.detail.message ?? ''; setTimeout(() => notification = '', 4000)">
    <aside class="w-full lg:w-60 shrink-0 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 lg:min-h-screen lg:border-b-0 lg:border-r p-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold text-brand-700 dark:text-brand-300">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">T</span>
            {{ __('common.app_name') }}
        </a>
        <nav class="mt-6 flex flex-wrap gap-1 lg:flex-col" aria-label="{{ __('common.home') }}">
            @php $navUser = auth()->user(); @endphp
            <a class="nav-link" href="{{ route('home') }}">{{ __('common.home') }}</a>
            <a class="nav-link" href="{{ route('search') }}">{{ __('common.search') }}</a>
            <a class="nav-link" href="{{ route('panduan') }}">{{ __('common.user_guide') }}</a>
            @can('viewAny', App\Models\Ticket::class)
                @if($navUser?->isRequester())
                    <a class="nav-link" href="{{ route('requester.requests') }}">{{ __('common.my_requests') }}</a>
                @else
                    <a class="nav-link" href="{{ route('tickets.index') }}">{{ __('common.tickets') }}</a>
                    @if($navUser?->isAgent() || $navUser?->isAdmin())
                        <a class="nav-link" href="{{ route('agent.queue') }}">{{ __('agent.queue') }}</a>
                    @endif
                    @can('viewAny', App\Models\Project::class)
                        <a class="nav-link" href="{{ route('projects.index') }}">{{ __('common.projects') }}</a>
                    @endcan
                @endif
            @endcan
            @can('viewAny', App\Models\User::class)
                <a class="nav-link" href="{{ route('admin.users.index') }}">{{ __('common.users') }}</a>
            @endcan
            @can('viewAny', App\Models\AuditLog::class)
                <a class="nav-link" href="{{ route('admin.audit-logs') }}">{{ __('common.audit_log') }}</a>
            @endcan
            @can('viewAny', App\Models\User::class)
                <a class="nav-link" href="{{ route('admin.settings.index') }}">{{ __('common.settings') }}</a>
                <a class="nav-link" href="{{ route('admin.settings.sla') }}">{{ __('sla.settings') }}</a>
            @endcan
            @if($navUser?->isAdmin())
                <a class="nav-link" href="{{ route('health') }}">{{ __('common.health') }}</a>
            @endif
        </nav>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3">
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-primary" x-shortcut="c" x-on:click="$dispatch('open-quick-create')"
                        @can('create', App\Models\Ticket::class) @else disabled @endcan>
                    + {{ __('common.create') }}
                </button>
                {{-- These pages already have their own scoped search field. --}}
                @unless(request()->routeIs('search', 'dev.list', 'agent.queue', 'requester.requests'))
                <form action="{{ route('tickets.index') }}" method="GET" role="search" class="hidden sm:block">
                    <label class="sr-only" for="global-search">{{ __('common.search') }}</label>
                    <input id="global-search" x-shortcut="/" name="q" type="search" placeholder="{{ __('common.search') }}"
                           value="{{ request('q') }}" class="input w-56">
                    {{-- Keep the active ticket filters when searching from the top bar. --}}
                    @foreach(['status', 'project', 'assignee', 'priority', 'sort', 'dir', 'per_page'] as $filterKey)
                        @if(request()->filled($filterKey))
                            <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                        @endif
                    @endforeach
                </form>
                @endunless
            </div>
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-ghost" x-on:click="help = true" x-shortcut="?"
                        title="{{ __('common.help') }}" aria-label="{{ __('common.help') }}">?</button>
                <form action="{{ route('theme.switch') }}" method="POST">
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
                <form action="{{ route('locale.switch') }}" method="POST">
                    @csrf
                    <label class="sr-only" for="locale">{{ __('common.language') }}</label>
                    <div class="flex items-center gap-1">
                        <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path stroke-linecap="round" d="M3.5 9h17M3.5 15h17M12 3c2.5 2.5 3.75 5.5 3.75 9S14.5 18.5 12 21c-2.5-2.5-3.75-5.5-3.75-9S9.5 5.5 12 3Z"/>
                        </svg>
                        <select id="locale" name="locale" class="input py-1" aria-label="{{ __('common.language') }}" onchange="this.form.submit()">
                            <option value="id" @selected(app()->getLocale() === 'id')>ID</option>
                            <option value="en" @selected(app()->getLocale() === 'en')>EN</option>
                        </select>
                    </div>
                </form>
                <span class="text-sm text-slate-600 dark:text-slate-400">{{ auth()->user()?->name }}</span>
                <form action="{{ route('logout') }}" method="POST">@csrf<button class="btn btn-ghost">{{ __('common.sign_out') }}</button></form>
            </div>
        </header>

        <main id="main-content" class="mx-auto max-w-7xl p-4 sm:p-6" tabindex="-1">
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
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('common.keyboard_hint') }}</p>
            <button class="btn btn-secondary mt-4" x-on:click="help = false">{{ __('common.cancel') }}</button>
        </div>
    </div>
</div>

<div x-show="notification" x-cloak aria-live="polite" role="status"
     x-transition:enter="ui-enter-active" x-transition:enter-start="ui-enter-start" x-transition:enter-end="ui-enter-end"
     x-transition:leave="ui-leave-active" x-transition:leave-start="ui-leave-start" x-transition:leave-end="ui-leave-end"
     class="toast-panel fixed bottom-4 right-4 z-50 max-w-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm shadow-lg"
     x-text="notification"></div>

@livewire('quick-create')
@livewireScripts
@stack('scripts')
</body>
</html>
