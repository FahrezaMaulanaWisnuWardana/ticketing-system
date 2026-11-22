<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('projects.show', $ticket->project) }}" class="text-sm text-brand-600">{{ $ticket->project->key }}</a>
            <h1 class="text-2xl font-semibold"><span translate="no">{{ $ticket->key }}</span> — {{ $ticket->title }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <x-status-badge :status="$ticket->status" />
                <x-badge :tone="$ticket->priority->value === 'urgent' ? 'rose' : 'slate'">{{ __('priorities.'.$ticket->priority->value) }}</x-badge>
                @php $slaStatus = app(App\Services\SlaService::class)->status($ticket); @endphp
                <x-badge :tone="$slaStatus === 'breached' ? 'rose' : ($slaStatus === 'at_risk' ? 'amber' : 'emerald')">{{ __('sla.'.$slaStatus) }}</x-badge>
                @if($ticket->blocked)<x-badge tone="rose">{{ __('common.blocked') }}</x-badge>@endif
                @if($trelloLink)
                    <a href="{{ $trelloLink->external_url ?? '#' }}" class="badge bg-slate-100 text-slate-700" target="_blank" rel="noopener noreferrer">Trello</a>
                @endif
            </div>
        </div>
        <div class="flex gap-2">
            @if($ticket->kind->value === 'request' && auth()->user()?->isAgent())
                <form method="POST" action="{{ route('tickets.dev', $ticket) }}">@csrf
                    <button class="btn btn-secondary" type="submit">{{ __('pm.create_dev_ticket') }}</button>
                </form>
            @endif
            @can('reopen', $ticket)<button type="button" class="btn btn-secondary" onclick="document.getElementById('reopen-form').submit()">{{ __('common.reopen') }}</button>@endcan
            <button wire:click="toggleWatch" class="btn btn-secondary">{{ $watchers->contains('user_id', auth()->id()) ? __('common.unwatch') : __('common.watch') }}</button>
        </div>
    </div>

    @if($trelloLink && $trelloLink->external_state->value !== 'ok')
        <div class="alert alert-error flex flex-wrap items-center justify-between" role="status">
            <span>{{ __('trello.changed_externally') }}</span>
            <span class="flex gap-2">
                @can('update', $ticket)
                    <form method="POST" action="{{ route('trello.resync', $ticket) }}">@csrf
                        <button class="btn btn-secondary text-xs" type="submit">{{ __('trello.resync') }}</button></form>
                    <form method="POST" action="{{ route('trello.unlink', $ticket) }}">@csrf
                        <button class="btn btn-ghost text-xs" type="submit">{{ __('trello.unlink') }}</button></form>
                @endcan
            </span>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <h2 class="mb-4 font-semibold">{{ __('common.description') }}</h2>
                @can('update', $ticket)
                <form wire:submit="saveDetails" class="space-y-4">
                    <x-input name="title" :label="__('common.title')" wire:model="title" />
                    <div class="space-y-1">
                        <label for="ticket-description" class="block text-sm font-medium text-slate-700">{{ __('common.description') }}</label>
                        <textarea id="ticket-description" wire:model="description" rows="5" class="input w-full"></textarea>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-1">
                            <label for="ticket-priority" class="block text-sm font-medium text-slate-700">{{ __('common.priority') }}</label>
                            <select id="ticket-priority" wire:model="priority" class="input w-full">
                                @foreach(App\Enums\Priority::cases() as $case)<option value="{{ $case->value }}">{{ __('priorities.'.$case->value) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label for="ticket-assignee" class="block text-sm font-medium text-slate-700">{{ __('common.assignee') }}</label>
                            <select id="ticket-assignee" wire:model="assigneeId" class="input w-full">
                                <option value="">—</option>
                                @foreach(App\Models\User::whereIn('role', ['agent', 'admin'])->orderBy('name')->get() as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">{{ __('common.save') }}</button>
                </form>
                @else
                    <div class="markdown">{!! $markdown->render((string) $ticket->description) !!}</div>
                @endcan
            </div>

            <div class="card">
                <h2 class="mb-4 font-semibold">{{ __('common.comments') }}</h2>
                <div class="space-y-4">
                    @forelse($comments as $comment)
                        <article class="rounded-lg border p-3 {{ $comment->is_internal ? 'border-amber-300 bg-amber-50' : 'border-slate-200' }}">
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>{{ $comment->user?->name }}</span>
                                <span>{{ App\Support\DateFormat::format($comment->created_at) }}</span>
                            </div>
                            <div class="markdown mt-2">{!! $markdown->render($comment->body) !!}</div>
                            @if($comment->attachments->isNotEmpty())
                                <ul class="mt-2 text-sm">@foreach($comment->attachments as $file)
                                    <li><a class="text-brand-600 underline" href="{{ route('attachments.show', $file) }}">{{ $file->original_name }}</a></li>
                                @endforeach</ul>
                            @endif
                            @can('update', $comment)
                                <form method="POST" action="{{ route('comments.update', [$ticket, $comment]) }}" class="mt-2">
                                    @csrf @method('PUT')
                                    <textarea name="body" rows="2" class="input w-full">{{ $comment->body }}</textarea>
                                    <button class="btn btn-ghost text-xs" type="submit">{{ __('common.save') }}</button>
                                </form>
                            @endcan
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('common.empty_hint') }}</p>
                    @endforelse
                </div>

                @can('create', [App\Models\Comment::class, $ticket])
                <form wire:submit="addComment" class="mt-5 space-y-3">
                    <div class="space-y-1">
                        <label for="new-comment" class="block text-sm font-medium text-slate-700">{{ __('common.reply') }}</label>
                        <textarea id="new-comment" wire:model="newComment" rows="4" class="input w-full" placeholder="{{ __('tickets.reply_placeholder') }}"></textarea>
                        @error('newComment')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @if($canSeeInternal)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isInternal"> {{ __('common.internal_note') }}</label>
                    @endif
                    <div class="space-y-1">
                        <label for="ticket-files" class="block text-sm font-medium text-slate-700">{{ __('common.attachments') }}</label>
                        <input id="ticket-files" type="file" wire:model="files" multiple class="input w-full">
                    </div>
                    <button class="btn btn-primary" type="submit">{{ __('common.send') }}</button>
                </form>
                @endcan
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <h2 class="mb-3 font-semibold">{{ __('common.status') }}</h2>
                @can('changeStatus', $ticket)
                    <div class="flex flex-wrap gap-2">
                        @foreach(App\Domain\TicketWorkflow::allowedNext($ticket->status) as $next)
                            <button wire:click="changeStatus('{{ $next->value }}')" class="btn btn-secondary text-xs">{{ __('status.'.$next->value) }}</button>
                        @endforeach
                    </div>
                @endcan
                @can('assign', $ticket)
                    <button wire:click="assignToMe" class="btn btn-secondary mt-3 w-full">{{ __('common.assign_to_me') }}</button>
                @endcan
            </div>

            <div class="card">
                <h2 class="mb-3 font-semibold">{{ __('common.attachments') }}</h2>
                <ul class="space-y-2 text-sm">
                    @forelse($attachments as $file)
                        <li class="flex items-center justify-between gap-2">
                            <a class="truncate text-brand-600 underline" href="{{ route('attachments.show', $file) }}">{{ $file->original_name }}</a>
                            @can('delete', $file)
                                <form method="POST" action="{{ route('attachments.destroy', $file) }}">@csrf @method('DELETE')
                                    <button class="text-xs text-rose-600" type="submit">{{ __('common.delete') }}</button>
                                </form>
                            @endcan
                        </li>
                    @empty<li class="text-slate-500">—</li>@endforelse
                </ul>
                @can('attach', $ticket)
                <form method="POST" action="{{ route('attachments.store', $ticket) }}" enctype="multipart/form-data" class="mt-3">
                    @csrf
                    <input type="file" name="file" class="input w-full text-xs" required>
                    <button class="btn btn-secondary mt-2 w-full" type="submit">{{ __('common.attachments') }}</button>
                </form>
                @endcan
            </div>

            @can('viewInternalNotes', $ticket)
                <div class="card">
                    <h2 class="mb-3 font-semibold">{{ __('common.activity') }}</h2>
                    <ul class="space-y-2 text-xs text-slate-600">
                        @foreach($activities as $activity)
                            <li>{{ $activity->event }} @if($activity->field)· {{ $activity->field }}@endif · {{ App\Support\DateFormat::format($activity->created_at) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endcan
        </div>
    </div>
</div>

<form id="reopen-form" method="POST" action="{{ route('tickets.reopen', $ticket) }}" class="hidden">@csrf</form>
