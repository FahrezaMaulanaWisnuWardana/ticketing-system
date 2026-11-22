<div wire:keydown.escape.window="close">
    @if($open)
        <div class="modal-overlay fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 pt-16">
            <div class="modal-panel card w-full max-w-lg" role="dialog" aria-modal="true" aria-label="{{ __('tickets.new_request') }}">
                <h2 class="mb-4 text-lg font-semibold">{{ __('tickets.new_request') }}</h2>
                <form wire:submit="create" class="space-y-4">
                    <div class="space-y-1">
                        <label for="qc-project" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('common.project') }}</label>
                        <select id="qc-project" wire:model="projectId" class="input w-full" required>
                            <option value="0">{{ __('tickets.select_project') }}</option>
                            @foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->name }} ({{ $project->key }})</option>@endforeach
                        </select>
                        @error('projectId')<p class="text-xs text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @can('createDev', App\Models\Ticket::class)
                        <div class="space-y-1">
                            <label for="qc-kind" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('common.tickets') }}</label>
                            <select id="qc-kind" wire:model="kind" class="input w-full">
                                <option value="request">request</option><option value="dev">dev</option>
                            </select>
                        </div>
                    @endcan
                    <div class="space-y-1">
                        <label for="qc-title" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('common.title') }}</label>
                        <input id="qc-title" wire:model="title" class="input w-full" maxlength="200" required>
                        @error('title')<p class="text-xs text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <x-rich-editor name="description" id="qc-description" wire="description"
                                   attachment-wire="quickAttachmentIds" :label="__('common.description')"
                                   :placeholder="__('tickets.description_placeholder')" />
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-secondary" wire:click="close">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('common.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
