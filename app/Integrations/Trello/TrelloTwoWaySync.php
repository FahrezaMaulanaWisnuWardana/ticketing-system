<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use App\Actions\UpdateTicket;
use App\Domain\TicketWorkflow;
use App\Enums\TicketStatus;
use App\Enums\TrelloExternalState;
use App\Integrations\Contracts\IntegrationAdapter;
use App\Models\Activity;
use App\Models\ExternalLink;
use App\Models\IntegrationConnection;
use App\Models\StatusMapping;
use App\Models\SyncConflict;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class TrelloTwoWaySync
{
    /** @var array<int, string> */
    private const FIELDS = ['title', 'description', 'due_date', 'status', 'assignee', 'labels'];

    public function __construct(private readonly TrelloConnectionService $service) {}

    /** @param array<string, mixed> $incoming */
    public function applyInbound(ExternalLink $link, array $incoming, string $memberName): void
    {
        $ticket = $link->ticket;
        if ($ticket === null) {
            return;
        }
        $ticket->loadMissing('project');
        $connection = IntegrationConnection::where('project_id', $ticket->project_id)->first();
        if ($connection === null || $connection->mode->value !== 'two_way' || $connection->status->value !== 'active') {
            return;
        }
        $this->assertWriteBudget($connection, $link);

        $stored = (array) ($link->field_hashes ?? []);
        $changes = [];

        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $incoming)) {
                continue;
            }
            $appValue = $this->appValue($ticket, $field);
            $trelloValue = (string) $incoming[$field];
            $appHash = hash('sha256', $appValue);
            $trelloHash = hash('sha256', $trelloValue);
            $previous = (string) ($stored[$field] ?? '');
            if ($appHash === $trelloHash) {
                continue;
            }
            $appChanged = $previous !== '' && $appHash !== $previous;
            $trelloChanged = $previous === '' || $trelloHash !== $previous;

            if ($appChanged && $trelloChanged) {
                SyncConflict::create([
                    'connection_id' => $connection->id, 'ticket_id' => $ticket->id, 'field' => $field,
                    'app_value' => $appValue, 'trello_value' => $trelloValue,
                    'resolution' => 'app_wins', 'resolved_at' => now(),
                ]);
                $this->pushField($link, $connection, $field, $appValue);
                $stored[$field] = $appHash;

                continue;
            }
            if ($trelloChanged) {
                $changes[$field] = $trelloValue;
                $stored[$field] = $trelloHash;
            } elseif ($appChanged) {
                $this->pushField($link, $connection, $field, $appValue);
                $stored[$field] = $appHash;
            }
        }

        if ($changes !== []) {
            $this->applyToApp($ticket, $changes, $connection, $memberName, $link);
        }
        $link->forceFill(['field_hashes' => $stored, 'last_inbound_at' => now()])->save();
    }

    /** @param array<string, string> $changes */
    private function applyToApp(Ticket $ticket, array $changes, IntegrationConnection $connection, string $memberName, ExternalLink $link): void
    {
        $actor = $this->systemUser();
        $data = [];
        foreach ($changes as $field => $value) {
            switch ($field) {
                case 'title': $data['title'] = mb_substr($value, 0, 200);
                    break;
                case 'description': $data['description'] = strip_tags($value);
                    break;
                case 'due_date': $data['due_date'] = $value === '' ? null : $value;
                    break;
                case 'status':
                    $status = StatusMapping::where('connection_id', $connection->id)
                        ->where('trello_list_id', $value)->value('ticket_status');
                    $status = $status instanceof TicketStatus ? $status : ($status === null ? null : TicketStatus::from((string) $status));
                    if ($status === null || ! TicketWorkflow::canTransition($ticket->status, $status)) {
                        $this->service->log($connection, 'warning', 'invalid_inbound_transition', ['list' => $value]);
                        $link->forceFill(['external_state' => TrelloExternalState::ChangedExternally])->save();
                        break;
                    }
                    $data['status'] = $status->value;
                    break;
                case 'assignee':
                    $data['assignee_id'] = User::whereRaw('lower(name) = ?', [mb_strtolower($value)])->value('id');
                    break;
            }
        }

        if ($data !== []) {
            app(UpdateTicket::class)($ticket, $actor, $data);
        }
        Activity::create([
            'ticket_id' => $ticket->id, 'user_id' => $actor->id,
            'event' => 'trello_inbound', 'source' => 'trello',
            'metadata' => ['member' => $memberName, 'fields' => array_keys($changes)],
        ]);
    }

    private function pushField(ExternalLink $link, IntegrationConnection $connection, string $field, string $value): void
    {
        $map = ['title' => 'name', 'description' => 'desc', 'due_date' => 'due'];
        $key = $map[$field] ?? null;
        if ($key === null) {
            return;
        }
        app(IntegrationAdapter::class)->updateCard($link->external_id, [$key => $value], $this->service->credentials($connection));
    }

    private function appValue(Ticket $ticket, string $field): string
    {
        return match ($field) {
            'title' => $ticket->title,
            'description' => (string) $ticket->description,
            'due_date' => (string) $ticket->due_date?->toDateString(),
            'status' => (string) StatusMapping::where('ticket_status', $ticket->status)->value('trello_list_id'),
            'assignee' => (string) $ticket->assignee?->name,
            'labels' => $ticket->labels()->pluck('name')->implode(','),
            default => '',
        };
    }

    private function systemUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'trello@system.local'],
            ['name' => 'Trello', 'role' => 'agent', 'is_active' => true, 'password' => null, 'email_verified_at' => now()],
        );
    }

    private function assertWriteBudget(IntegrationConnection $connection, ExternalLink $link): void
    {
        $key = "trello.writes.{$link->ticket_id}.".(int) floor(time() / 60);
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addMinute());
        if ($count > (int) config('trello.max_sync_writes_per_minute')) {
            $connection->forceFill(['status' => 'error', 'last_error' => 'Sync write budget exceeded'])->save();
            throw new TrelloRateLimitException('Sync write budget exceeded for ticket '.$link->ticket_id);
        }
    }
}
