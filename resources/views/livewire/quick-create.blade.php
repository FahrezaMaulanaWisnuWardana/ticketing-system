<div x-data="{ open: @entangle('open') }" x-on:open-quick-create.window="open = true" x-on:keydown.escape.window="open = false">
    <x-modal :title="__('tickets.new_request')" show="open">
        <form wire:submit="create" class="space-y-4">
            <div class="space-y-1">
                <label for="qc-project" class="block text-sm font-medium text-slate-700">{{ __('common.project') }}</label>
                <select id="qc-project" wire:model="projectId" class="input w-full" required>
                    <option value="0">{{ __('tickets.select_project') }}</option>
                    @foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->name }} ({{ $project->key }})</option>@endforeach
                </select>
                @error('projectId')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
            </div>
            @can('createDev', App\Models\Ticket::class)
            <div class="space-y-1">
                <label for="qc-kind" class="block text-sm font-medium text-slate-700">{{ __('common.tickets') }}</label>
                <select id="qc-kind" wire:model="kind" class="input w-full">
                    <option value="request">request</option><option value="dev">dev</option>
                </select>
            </div>
            @endcan
            <div class="space-y-1">
                <label for="qc-title" class="block text-sm font-medium text-slate-700">{{ __('common.title') }}</label>
                <input id="qc-title" wire:model="title" class="input w-full" maxlength="200" required>
                @error('title')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="space-y-1">
                <label for="qc-description" class="block text-sm font-medium text-slate-700">{{ __('common.description') }}</label>
                <textarea id="qc-description" wire:model="description" class="input w-full" rows="3"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" x-on:click="open = false">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('common.create') }}</button>
            </div>
        </form>
    </x-modal>
</div>
