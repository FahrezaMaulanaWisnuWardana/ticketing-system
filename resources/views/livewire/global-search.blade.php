<div class="space-y-4" x-data="{}">
    <label class="sr-only" for="global-search-input">{{ __('common.search') }}</label>
    <input id="global-search-input" wire:model.live.debounce.250ms="term" x-shortcut="/" class="input w-full max-w-xl"
           placeholder="{{ __('search.placeholder') }}" autofocus>

    @if($results !== null)
        @if($results->isEmpty())
            <x-empty-state />
        @else
            <div class="card overflow-x-auto">
                <table class="table">
                    <thead><tr><th>{{ __('common.title') }}</th><th>{{ __('common.project') }}</th><th>{{ __('common.status') }}</th></tr></thead>
                    <tbody>@foreach($results as $ticket)
                        <tr class="border-t border-slate-100">
                            <td><a class="font-medium text-brand-700 hover:underline" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->key }} — {{ $ticket->title }}</a></td>
                            <td>{{ $ticket->project?->key }}</td>
                            <td><x-status-badge :status="$ticket->status" :requester="auth()->user()->isRequester()" /></td>
                        </tr>
                    @endforeach</tbody>
                </table>
            </div>
            {{ $results->links() }}
        @endif
    @endif
</div>
