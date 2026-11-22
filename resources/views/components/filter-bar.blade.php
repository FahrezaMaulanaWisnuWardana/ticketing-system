@props([
    'action',
    'filters' => [],
    'typeOptions' => [],
    'showCreator' => false,
    'showType' => true,
    'idPrefix' => 'filter',
    'hasActiveFilters' => false,
    'resetUrl' => null,
    'submitLabel' => null,
])
<form method="GET" action="{{ $action }}" role="search" class="card !p-3">
    <div class="flex flex-wrap items-end gap-3">
        @if($showCreator)
            <div class="w-full sm:w-auto">
                <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400" for="{{ $idPrefix }}-creator">{{ __('portal.list.created_by') }}</label>
                <select id="{{ $idPrefix }}-creator" name="creator" class="input w-full py-1.5 sm:w-48">
                    <option value="mine" @selected(($filters['creator'] ?? 'mine') === 'mine')>{{ __('portal.list.created_by_mine') }}</option>
                    <option value="all" @selected(($filters['creator'] ?? '') === 'all')>{{ __('portal.list.created_by_all') }}</option>
                </select>
            </div>
        @endif

        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400" for="{{ $idPrefix }}-status">{{ __('portal.list.status_filter') }}</label>
            <select id="{{ $idPrefix }}-status" name="status" class="input w-full py-1.5 sm:w-44">
                <option value="open" @selected(($filters['status'] ?? 'open') === 'open')>{{ __('portal.list.status_open') }}</option>
                <option value="done" @selected(($filters['status'] ?? '') === 'done')>{{ __('portal.list.status_done') }}</option>
                <option value="all" @selected(($filters['status'] ?? '') === 'all')>{{ __('portal.list.status_all') }}</option>
            </select>
        </div>

        @if($showType)
            <div class="w-full sm:w-auto">
                <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400" for="{{ $idPrefix }}-type">{{ __('portal.list.type') }}</label>
                <select id="{{ $idPrefix }}-type" name="type" class="input w-full py-1.5 sm:w-52">
                    <option value="">{{ __('portal.list.all_types') }}</option>
                    @foreach($typeOptions as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['type'] ?? null) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="min-w-0 flex-1 sm:min-w-48">
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400" for="{{ $idPrefix }}-search">{{ __('portal.list.search') }}</label>
            <input id="{{ $idPrefix }}-search" name="q" type="search" value="{{ $filters['q'] ?? '' }}"
                   placeholder="{{ __('portal.list.search_placeholder') }}" class="input w-full py-1.5">
        </div>

        <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'created_at' }}">
        <input type="hidden" name="dir" value="{{ $filters['dir'] ?? 'desc' }}">

        <button type="submit" class="btn btn-secondary py-1.5">{{ $submitLabel ?? __('common.filter') }}</button>
        @if($hasActiveFilters && $resetUrl)
            <a href="{{ $resetUrl }}" class="btn btn-ghost py-1.5">{{ __('portal.list.reset') }}</a>
        @endif
    </div>
</form>
