@extends('layouts.app')
@section('title', __('sla.settings'))
@section('content')
<div class="card max-w-3xl">
    <h1 class="mb-5 text-xl font-semibold">{{ __('sla.settings') }}</h1>
    <form method="POST" action="{{ route('admin.settings.sla.update') }}" class="space-y-4">
        @csrf @method('PUT')
        <div class="grid gap-3 sm:grid-cols-2">
            <x-input name="business_start" :label="__('sla.start')" :value="$hours['start'] ?? '08:00'" required />
            <x-input name="business_end" :label="__('sla.end')" :value="$hours['end'] ?? '17:00'" required />
        </div>
        <fieldset class="space-y-1">
            <legend class="text-sm font-medium">{{ __('sla.workdays') }}</legend>
            @foreach(explode(',', __('sla.day_names')) as $index => $day)
                <label class="mr-4 text-sm"><input type="checkbox" name="workdays[]" value="{{ $index + 1 }}" @checked(in_array($index + 1, (array) ($hours['days'] ?? [])))> {{ $day }}</label>
            @endforeach
        </fieldset>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="urgent_24x7" value="1" @checked($urgent24x7)> {{ __('sla.urgent_24x7') }}</label>

        <h2 class="pt-2 font-semibold">{{ __('sla.targets') }}</h2>
        <div class="grid gap-3 sm:grid-cols-4">
            @foreach(App\Enums\Priority::cases() as $priority)
                <div class="rounded-lg border p-3">
                    <p class="mb-2 text-sm font-medium">{{ __('priorities.'.$priority->value) }}</p>
                    <label class="block text-xs">{{ __('sla.response_minutes') }}
                        <input class="input mt-1 w-full" type="number" min="1" name="targets[{{ $priority->value }}][response]"
                               value="{{ $targets[$priority->value]['response'] ?? 480 }}" required>
                    </label>
                    <label class="mt-2 block text-xs">{{ __('sla.resolve_minutes') }}
                        <input class="input mt-1 w-full" type="number" min="1" name="targets[{{ $priority->value }}][resolve]"
                               value="{{ $targets[$priority->value]['resolve'] ?? 1440 }}" required>
                    </label>
                </div>
            @endforeach
        </div>
        <button class="btn btn-primary" type="submit">{{ __('common.save') }}</button>
    </form>
</div>

<div class="card mt-6 max-w-3xl">
    <h2 class="mb-3 font-semibold">{{ __('sla.holidays') }}</h2>
    <ul class="mb-4 space-y-1 text-sm">@foreach($holidays as $holiday)
        <li class="flex justify-between"><span>{{ $holiday->date->format('Y-m-d') }} — {{ $holiday->name }}</span>
            <form method="POST" action="{{ route('admin.settings.sla.holidays.destroy', $holiday) }}">@csrf @method('DELETE')
                <button class="text-rose-600 dark:text-rose-400">{{ __('common.delete') }}</button></form>
        </li>
    @endforeach</ul>
    <form method="POST" action="{{ route('admin.settings.sla.holidays.store') }}" class="flex flex-wrap gap-2">
        @csrf
        <input type="date" name="date" class="input" required>
        <input type="text" name="name" class="input flex-1" placeholder="{{ __('sla.holiday_name') }}" required>
        <button class="btn btn-secondary" type="submit">{{ __('common.create') }}</button>
    </form>
</div>
@endsection
