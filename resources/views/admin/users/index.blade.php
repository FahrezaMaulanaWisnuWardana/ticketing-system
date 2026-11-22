@extends('layouts.app')
@section('title', __('common.users'))
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">{{ __('common.users') }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ {{ __('admin.invite') }}</a>
        <a href="{{ route('admin.users.import') }}" class="btn btn-secondary">{{ __('admin.import_users') }}</a>
    </div>
</div>
<div class="card overflow-x-auto">
    <table class="table">
        <thead><tr><th>{{ __('common.name') }}</th><th>{{ __('common.email') }}</th><th>{{ __('common.role') }}</th><th>{{ __('common.status') }}</th><th>{{ __('common.actions') }}</th></tr></thead>
        <tbody>@foreach($users as $user)
            <tr class="border-t border-slate-100 dark:border-slate-800">
                <td>{{ $user->name }}</td><td>{{ $user->email }}</td>
                <td>{{ __('roles.'.$user->role->value) }}</td>
                <td><x-badge :tone="$user->is_active ? 'emerald' : 'rose'">{{ $user->is_active ? __('common.yes') : __('common.no') }}</x-badge></td>
                <td>
                    <div class="flex flex-wrap gap-2">
                        <a class="text-brand-600 dark:text-brand-400" href="{{ route('admin.users.edit', $user) }}">{{ __('common.edit') }}</a>
                        @if($user->is_active)
                            <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">@csrf<button class="text-rose-600 dark:text-rose-400">{{ __('admin.deactivate') }}</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.users.activate', $user) }}">@csrf<button class="text-emerald-600 dark:text-emerald-400">{{ __('admin.activate') }}</button></form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach</tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
