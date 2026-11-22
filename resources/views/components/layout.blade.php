@props(['title' => null])
@php
    use App\Models\AuditLog;
    use App\Models\Project;
    use App\Models\Ticket;
    use App\Models\User;
    use App\Support\Queries\AgentQueueQuery;
    use App\Support\RequestTypeCatalog;

    $navUser = auth()->user();
    $isAgent = $navUser !== null && ($navUser->isAgent() || $navUser->isAdmin());
    $queueCounts = $isAgent ? AgentQueueQuery::countsFor($navUser) : [];
    $pickerTypes = RequestTypeCatalog::grouped();
    $queueLinks = [
        'all_open' => 'portal.queues.all_open',
        'mine' => 'portal.queues.mine',
        'unassigned' => 'portal.queues.unassigned',
        'new' => 'portal.queues.new',
        'recently_resolved' => 'portal.queues.recently_resolved',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @isset($cspNonce)<meta name="csp-nonce" content="{{ $cspNonce }}">@endisset
    <meta name="reverb-key" content="{{ config('reverb.apps.apps.0.key', '') }}">
    <meta name="echo-enabled" content="{{ config('broadcasting.default') === 'reverb' ? 'true' : 'false' }}">
    <title>{{ $title ?? __('common.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen overflow-x-hidden">
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white dark:focus:bg-slate-900 focus:px-4 focus:py-2 focus:shadow">{{ __('common.skip_to_content') }}</a>
<div class="min-h-screen lg:flex"
     x-data="{ help: false, picker: false, userMenu: false, mobileNav: false, notification: '' }"
     x-on:notify.window="notification = $event.detail.message ?? ''; setTimeout(() => notification = '', 4000)">

    {{-- Sidebar: compact bar on mobile, full navigation on desktop ------ --}}
    <aside class="w-full shrink-0 border-b border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900 lg:w-64 lg:min-h-screen lg:border-b-0 lg:border-r lg:p-4">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 font-semibold text-brand-700 dark:text-brand-300">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">T</span>
                <span class="min-w-0">
                    <span class="block truncate">{{ __('common.app_name') }}</span>
                    <span class="block text-[11px] font-normal text-slate-500 dark:text-slate-400">{{ __('portal.app_tagline') }}</span>
                </span>
            </a>
            <button type="button" class="btn btn-ghost h-9 w-9 shrink-0 px-0 lg:hidden"
                    x-on:click="mobileNav = ! mobileNav" :aria-expanded="mobileNav"
                    aria-controls="portal-main-nav" aria-label="{{ __('portal.nav.toggle_navigation') }}"
                    title="{{ __('portal.nav.toggle_navigation') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>

        <nav id="portal-main-nav"
             class="mt-4 flex-col space-y-1 lg:!flex"
             :class="mobileNav ? 'flex' : 'hidden'"
             aria-label="{{ __('common.home') }}">
            <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('portal.nav.section_requests') }}</p>
            <a href="{{ route('home') }}" @class(['nav-link', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('home')])>
                {{ __('portal.nav.home') }}
            </a>
            @can('viewAny', Ticket::class)
                <a href="{{ route('requester.requests') }}" @class(['nav-link', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('requester.requests')])>
                    {{ __('portal.nav.my_requests') }}
                </a>
            @endcan
            <a href="{{ route('panduan') }}" @class(['nav-link', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('panduan')])>
                {{ __('portal.nav.guide') }}
            </a>

            @if($isAgent)
                <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('portal.queues.title') }}</p>
                @foreach($queueLinks as $key => $label)
                    <a href="{{ route('agent.queue', ['queue' => $key]) }}"
                       @class(['nav-link flex items-center justify-between gap-2', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('agent.queue') && request('queue', 'all_open') === $key])>
                        <span class="truncate">{{ __($label) }}</span>
                        <span class="tabular shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $queueCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            @endif

            @can('viewAny', User::class)
                <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('portal.nav.section_manage') }}</p>
                <a href="{{ route('projects.index') }}" @class(['nav-link', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('projects.*')])>{{ __('common.projects') }}</a>
                <a href="{{ route('tickets.index') }}" @class(['nav-link', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('tickets.index')])>{{ __('common.tickets') }}</a>
                <a href="{{ route('admin.users.index') }}" @class(['nav-link', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('admin.users.*')])>{{ __('common.users') }}</a>
                <a href="{{ route('admin.settings.index') }}" @class(['nav-link', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('admin.settings.*')])>{{ __('common.settings') }}</a>
            @endcan
            @can('viewAny', AuditLog::class)
                <a href="{{ route('admin.audit-logs') }}" @class(['nav-link', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('admin.audit-logs')])>{{ __('common.audit_log') }}</a>
            @endcan
            @if($navUser?->isAdmin())
                <a href="{{ route('health') }}" @class(['nav-link', 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200' => request()->routeIs('health')])>{{ __('common.health') }}</a>
            @endif
        </nav>
    </aside>

    {{-- Main column ---------------------------------------------------- --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="flex min-w-0 flex-wrap items-center gap-2 px-3 py-2 sm:gap-3 sm:px-4">
                @can('create', Ticket::class)
                    <button type="button" class="btn btn-primary shrink-0" x-shortcut="c" x-on:click="picker = true"
                            aria-haspopup="dialog" aria-label="{{ __('portal.nav.create') }}">
                        <span aria-hidden="true">+</span><span class="hidden sm:inline">{{ __('portal.nav.create') }}</span>
                    </button>
                @endcan

                @unless(request()->routeIs('search'))
                    <form action="{{ route('search') }}" method="GET" role="search"
                          class="order-last w-full min-w-0 sm:order-none sm:ml-1 sm:w-auto sm:max-w-sm sm:flex-1">
                        <label class="sr-only" for="topbar-search">{{ __('portal.nav.global_search') }}</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="m16 16 4 4"/>
                            </svg>
                            <input id="topbar-search" name="q" type="search" x-shortcut="/"
                                   placeholder="{{ __('portal.nav.global_search') }}"
                                   aria-label="{{ __('portal.nav.global_search_aria') }}"
                                   value="{{ request('q') }}"
                                   class="input w-full py-1.5 pl-9">
                        </div>
                    </form>
                @endunless

                <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
                    <button type="button" class="btn btn-ghost h-9 w-9 px-0" x-on:click="help = true" x-shortcut="?"
                            title="{{ __('portal.nav.help') }}" aria-label="{{ __('portal.nav.help') }}">?</button>

                    <form action="{{ route('theme.switch') }}" method="POST" class="shrink-0">
                        @csrf
                        <input type="hidden" name="theme" value="{{ ($theme ?? 'light') === 'dark' ? 'light' : 'dark' }}">
                        <button type="submit" class="btn btn-secondary h-9 w-9 px-0"
                                title="{{ ($theme ?? 'light') === 'dark' ? __('common.theme_light') : __('common.theme_dark') }}"
                                aria-label="{{ ($theme ?? 'light') === 'dark' ? __('common.theme_light') : __('common.theme_dark') }}">
                            @if(($theme ?? 'light') === 'dark')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                            @else
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 0 1 8.646 3.646 9 9 0 1 0 20.354 15.354Z"/></svg>
                            @endif
                        </button>
                    </form>

                    <form action="{{ route('locale.switch') }}" method="POST" class="shrink-0">
                        @csrf
                        <label class="sr-only" for="topbar-locale">{{ __('portal.nav.language') }}</label>
                        <select id="topbar-locale" name="locale" class="input h-9 w-auto py-1 text-xs"
                                aria-label="{{ __('portal.nav.language') }}" onchange="this.form.submit()">
                            <option value="id" @selected(app()->getLocale() === 'id')>ID</option>
                            <option value="en" @selected(app()->getLocale() === 'en')>EN</option>
                        </select>
                    </form>

                    <div class="relative shrink-0" x-on:click.outside="userMenu = false">
                        <button type="button" class="btn btn-ghost h-9 gap-2 px-1.5" x-on:click="userMenu = ! userMenu"
                                :aria-expanded="userMenu" aria-haspopup="menu" aria-label="{{ __('portal.nav.user_menu') }}">
                            <x-avatar :name="$navUser?->name" />
                            <span class="hidden max-w-32 truncate text-sm lg:inline">{{ $navUser?->name }}</span>
                            <svg class="hidden h-3.5 w-3.5 text-slate-400 lg:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="userMenu" x-cloak class="absolute right-0 z-40 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900" role="menu">
                            <p class="truncate px-2 py-1.5 text-sm font-medium text-slate-800 dark:text-slate-200">{{ $navUser?->name }}</p>
                            <p class="truncate px-2 pb-2 text-xs text-slate-500 dark:text-slate-400">{{ $navUser?->email }}</p>
                            <form action="{{ route('logout') }}" method="POST" role="menuitem">@csrf
                                <button class="btn btn-ghost w-full justify-start">{{ __('portal.nav.sign_out') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-6xl min-w-0 flex-1 p-4 sm:p-6" tabindex="-1">
            @if(session('status'))<div class="alert mb-4" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())
                <div class="alert alert-error mb-4" role="alert">
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>

    {{-- Help modal ----------------------------------------------------- --}}
    <div x-show="help" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-on:keydown.escape.window="help = false">
        <div class="card max-w-md" role="dialog" aria-modal="true" aria-label="{{ __('common.shortcuts') }}">
            <h2 class="text-lg font-semibold">{{ __('common.shortcuts') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('common.keyboard_hint') }}</p>
            <button class="btn btn-secondary mt-4" x-on:click="help = false">{{ __('common.cancel') }}</button>
        </div>
    </div>

    {{-- Request type picker (opened by "+ Buat" in the sidebar/top bar) --}}
    <div x-show="picker" x-cloak class="modal-overlay fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 pt-10 sm:pt-16"
         x-on:keydown.escape.window="picker = false">
        <div class="modal-panel card w-full max-w-2xl" role="dialog" aria-modal="true" aria-label="{{ __('portal.picker.title') }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('portal.picker.title') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('portal.picker.hint') }}</p>
                </div>
                <button type="button" class="btn btn-ghost h-8 w-8 px-0" x-on:click="picker = false" aria-label="{{ __('portal.picker.cancel') }}">×</button>
            </div>
            <div class="mt-5 max-h-[60vh] space-y-5 overflow-y-auto pr-1">
                @foreach($pickerTypes as $group => $types)
                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            {{ __(RequestTypeCatalog::groups()[$group] ?? '') }}
                        </h3>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($types as $type)
                                <x-request-type-card :type="$type" compact />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div x-show="notification" x-cloak aria-live="polite" role="status"
         x-transition:enter="ui-enter-active" x-transition:enter-start="ui-enter-start" x-transition:enter-end="ui-enter-end"
         x-transition:leave="ui-leave-active" x-transition:leave-start="ui-leave-start" x-transition:leave-end="ui-leave-end"
         class="toast-panel fixed bottom-4 right-4 z-50 max-w-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm shadow-lg"
         x-text="notification"></div>
</div>

@livewire('quick-create')
@livewireScripts
@stack('scripts')
</body>
</html>
