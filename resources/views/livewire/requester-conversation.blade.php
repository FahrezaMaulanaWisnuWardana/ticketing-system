<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div>
            <a class="text-sm text-brand-600" href="{{ route('requester.requests') }}">{{ __('common.my_requests') }}</a>
            <h1 class="mt-1 text-2xl font-semibold"><span translate="no">{{ $ticket->key }}</span> — {{ $ticket->title }}</h1>
            <div class="mt-2 flex items-center gap-2">
                <x-status-badge :status="$ticket->status" requester />
                @php $slaStatus = app(App\Services\SlaService::class)->status($ticket); @endphp
                <x-badge :tone="$slaStatus === 'breached' ? 'rose' : ($slaStatus === 'at_risk' ? 'amber' : 'emerald')">{{ __('sla.'.$slaStatus) }}</x-badge>
                @if($ticket->assignee)<span class="text-sm text-slate-500">{{ __('common.assignee') }}: {{ $ticket->assignee->name }}</span>@endif
            </div>
        </div>

        <div class="card">
            <h2 class="mb-3 font-semibold">{{ __('common.description') }}</h2>
            <div class="markdown">{!! $markdown->render((string) $ticket->description) !!}</div>
        </div>

        <div class="card">
            <h2 class="mb-4 font-semibold">{{ __('common.comments') }}</h2>
            <div class="space-y-4">
                @forelse($comments as $comment)
                    <article class="rounded-lg border border-slate-200 p-3">
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
                    </article>
                @empty
                    <p class="text-sm text-slate-500">{{ __('common.empty_hint') }}</p>
                @endforelse
            </div>

            @can('comment', $ticket)
            <form wire:submit="reply" class="mt-5 space-y-3">
                <label for="requester-reply" class="block text-sm font-medium text-slate-700">{{ __('common.reply') }}</label>
                <textarea id="requester-reply" wire:model="replyBody" rows="4" class="input w-full" placeholder="{{ __('tickets.reply_placeholder') }}"></textarea>
                @error('replyBody')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
                <label for="requester-files" class="sr-only">{{ __('common.attachments') }}</label>
                <input id="requester-files" type="file" wire:model="files" multiple class="input w-full text-xs">
                <button class="btn btn-primary" type="submit">{{ __('common.send') }}</button>
            </form>
            @endcan
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <h2 class="mb-3 font-semibold">{{ __('common.attachments') }}</h2>
            <ul class="space-y-2 text-sm">@forelse($attachments as $file)
                <li><a class="text-brand-600 underline" href="{{ route('attachments.show', $file) }}">{{ $file->original_name }}</a></li>
            @empty<li class="text-slate-500">—</li>@endforelse</ul>
        </div>

        @if($canReopen)
            <form method="POST" action="{{ route('tickets.reopen', $ticket) }}" class="card">
                @csrf
                <p class="mb-3 text-sm text-slate-600">{{ __('requester.reopen_hint') }}</p>
                <button class="btn btn-secondary w-full" type="submit">{{ __('common.reopen') }}</button>
            </form>
        @endif
    </div>
</div>
