<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Models\GitLink;
use App\Models\Ticket;
use App\Services\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class ProcessGitWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly string $provider,
        public readonly array $payload,
        public readonly string $delivery,
    ) {}

    public function handle(): void
    {
        $text = json_encode($this->payload, JSON_THROW_ON_ERROR);
        if (preg_match_all('/\b([A-Z]{2,10})-(\d+)\b/', $text, $matches) === 0) {
            return;
        }

        foreach (array_unique($matches[0]) as $key) {
            [$projectKey, $number] = explode('-', $key);
            $ticket = Ticket::withoutGlobalScopes()
                ->with('project')
                ->whereHas('project', static fn ($query) => $query->where('key', $projectKey))
                ->where('number', (int) $number)
                ->first();
            if ($ticket === null) {
                continue;
            }
            $this->link($ticket, $key);
        }
    }

    private function link(Ticket $ticket, string $key): void
    {
        $externalId = $this->provider.':'.$key.':'.md5(json_encode($this->payload, JSON_THROW_ON_ERROR));
        $state = $this->isMerged() ? 'merged' : 'open';
        GitLink::updateOrCreate(
            ['provider' => $this->provider, 'external_id' => $externalId],
            [
                'ticket_id' => $ticket->id,
                'ref' => (string) ($this->payload['ref'] ?? $key),
                'url' => isset($this->payload['html_url']) ? (string) $this->payload['html_url'] : null,
                'state' => $state,
            ],
        );

        if ($this->isMerged() && in_array($ticket->status, [TicketStatus::InReview, TicketStatus::InProgress], true)) {
            DB::transaction(static function () use ($ticket): void {
                $ticket->update(['status' => TicketStatus::Done, 'resolved_at' => now()]);
                ActivityLogger::log($ticket, 'git.merged', null, null, $ticket->status->value);
            });
        }
    }

    private function isMerged(): bool
    {
        $action = (string) ($this->payload['action'] ?? '');
        $pullRequest = $this->payload['pull_request'] ?? null;

        return $action === 'closed'
            && is_array($pullRequest)
            && (bool) ($pullRequest['merged'] ?? false);
    }
}
