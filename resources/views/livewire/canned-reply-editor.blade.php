<div class="space-y-4">
    <ul class="space-y-2">
        @forelse($replies as $reply)
            <li class="flex items-center justify-between rounded-lg border border-slate-200 p-2 text-sm">
                <span><strong>{{ $reply->title }}</strong> — {{ \Illuminate\Support\Str::limit($reply->body, 80) }}</span>
                <button class="text-xs text-rose-600" wire:click="delete({{ $reply->id }})"
                        onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button>
            </li>
        @empty
            <li class="text-sm text-slate-500">{{ __('common.empty_hint') }}</li>
        @endforelse
    </ul>
    <form wire:submit="save" class="space-y-2">
        <label class="sr-only" for="canned-title">{{ __('canned.title') }}</label>
        <input id="canned-title" wire:model="title" class="input w-full" placeholder="{{ __('canned.title') }}" required>
        @error('title')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
        <label class="sr-only" for="canned-body">{{ __('canned.body') }}</label>
        <textarea id="canned-body" wire:model="body" rows="3" class="input w-full" placeholder="{{ __('canned.body') }}" required></textarea>
        @error('body')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
        <button class="btn btn-secondary w-full" type="submit">{{ __('common.create') }}</button>
    </form>
</div>
