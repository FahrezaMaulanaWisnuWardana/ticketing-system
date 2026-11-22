<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use App\Actions\AddAttachment;
use App\Domain\TicketNumbering;
use App\Enums\TicketKind;
use App\Enums\TrelloExternalState;
use App\Integrations\Contracts\IntegrationAdapter;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\ExternalLink;
use App\Models\ImportRun;
use App\Models\IntegrationConnection;
use App\Models\Label;
use App\Models\MemberMapping;
use App\Models\StatusMapping;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TrelloImporter
{
    public function __construct(
        private readonly TrelloClient $client,
        private readonly TrelloConnectionService $service,
    ) {}

    public function import(ImportRun $run, bool $updateExisting = false, bool $includeArchived = false): void
    {
        $connection = $run->connection;
        if ($connection === null) {
            return;
        }
        $credentials = $this->service->credentials($connection);
        $run->update(['status' => 'running', 'started_at' => now()]);
        $counts = ['cards' => 0, 'comments' => 0, 'attachments' => 0, 'checklists' => 0, 'skipped' => 0];
        $errors = [];

        try {
            $board = app(IntegrationAdapter::class)->fetchBoard($connection->trello_board_id, $credentials);
            $cards = array_values((array) ($board['cards'] ?? []));
            if (count($cards) > (int) config('trello.max_cards')) {
                throw new TrelloException(__('trello.board_too_large'));
            }
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed', 'errors' => [['message' => $exception->getMessage()]], 'finished_at' => now(),
            ]);
            $this->service->recordFailure($connection, $exception->getMessage());

            return;
        }

        $statusMap = StatusMapping::where('connection_id', $connection->id)->pluck('ticket_status', 'trello_list_id');
        $memberMap = MemberMapping::where('connection_id', $connection->id)->pluck('user_id', 'trello_member_id');

        foreach ($cards as $index => $card) {
            if (! is_array($card)) {
                continue;
            }
            try {
                if (! empty($card['closed']) && ! $includeArchived) {
                    $counts['skipped']++;

                    continue;
                }
                $result = $this->importCard($run, $card, $index, $statusMap, $memberMap, $board, $credentials, $updateExisting, $includeArchived, $counts);
                if ($result === 'skipped') {
                    $counts['skipped']++;
                } else {
                    $counts['cards']++;
                }
            } catch (\Throwable $exception) {
                $errors[] = ['card' => $card['id'] ?? null, 'message' => $exception->getMessage()];
                $this->service->log($connection, 'error', 'card_import_failed', ['card' => $card['id'] ?? null, 'message' => $exception->getMessage()]);
            }
        }

        $run->update([
            'status' => $errors === [] ? 'completed' : 'partial',
            'counts' => $counts,
            'errors' => $errors,
            'finished_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  Collection<string, mixed>  $statusMap
     * @param  Collection<string, mixed>  $memberMap
     * @param  array<string, mixed>  $board
     * @param  array<string, string>  $credentials
     * @param  array<string, int>  $counts
     */
    private function importCard(
        ImportRun $run,
        array $card,
        int $index,
        $statusMap,
        $memberMap,
        array $board,
        array $credentials,
        bool $updateExisting,
        bool $includeArchived,
        array &$counts,
    ): string {
        $connection = $run->connection;
        if ($connection === null) {
            return 'skipped';
        }
        $externalId = (string) ($card['id'] ?? '');
        $existing = ExternalLink::where('provider', 'trello')->where('external_id', $externalId)->first();
        if ($existing !== null && ! $updateExisting) {
            return 'skipped';
        }

        $project = $connection->project;
        if ($project === null) {
            return 'skipped';
        }

        return DB::transaction(function () use ($run, $connection, $project, $card, $index, $statusMap, $memberMap, $board, $credentials, $existing, $externalId, $includeArchived, &$counts): string {
            $title = mb_substr((string) ($card['name'] ?? 'Imported card'), 0, 200);
            $description = strip_tags((string) ($card['desc'] ?? ''));
            $status = ! empty($card['closed']) && $includeArchived
                ? 'closed'
                : $statusMap->get((string) ($card['idList'] ?? ''), 'backlog');
            $due = isset($card['due']) && is_string($card['due']) ? substr($card['due'], 0, 10) : null;
            $assigneeId = null;
            foreach ((array) ($card['idMembers'] ?? []) as $trelloMemberId) {
                $candidate = $memberMap->get((string) $trelloMemberId);
                if ($candidate !== null) {
                    $assigneeId = (int) $candidate;
                    break;
                }
            }

            if ($existing !== null) {
                $ticket = $existing->ticket;
                if ($ticket === null) {
                    return 'skipped';
                }
                $ticket->update([
                    'title' => $title, 'description' => $description, 'status' => $status,
                    'due_date' => $due, 'assignee_id' => $assigneeId,
                ]);
            } else {
                $ticket = Ticket::create([
                    'project_id' => $project->id,
                    'number' => TicketNumbering::next($project->id),
                    'kind' => TicketKind::Dev,
                    'type' => 'task',
                    'title' => $title,
                    'description' => $description,
                    'status' => $status,
                    'priority' => 'normal',
                    'reporter_id' => $run->started_by,
                    'requester_id' => $run->started_by,
                    'assignee_id' => $assigneeId,
                    'due_date' => $due,
                    'rank' => str_pad((string) ($index + 1), 10, '0', STR_PAD_LEFT),
                ]);
                ExternalLink::create([
                    'ticket_id' => $ticket->id,
                    'provider' => 'trello',
                    'external_id' => $externalId,
                    'external_url' => $card['url'] ?? null,
                    'external_state' => TrelloExternalState::Ok,
                ]);
            }

            $this->importChecklists($ticket, $card, $counts);
            $this->importComments($ticket, $card, $board, $run->started_by, $memberMap, $counts);
            $this->importAttachments($ticket, $card, $run->started_by, $credentials, $counts, $connection);
            $this->importLabels($ticket, $card, $board, $connection);
            Activity::create([
                'ticket_id' => $ticket->id, 'user_id' => $run->started_by,
                'event' => 'imported_from_trello', 'source' => 'trello', 'metadata' => ['card' => $externalId],
            ]);

            return $existing === null ? 'created' : 'updated';
        });
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  array<string, int>  $counts
     */
    private function importChecklists(Ticket $ticket, array $card, array &$counts): void
    {
        foreach ((array) ($card['checklists'] ?? []) as $checklist) {
            if (! is_array($checklist)) {
                continue;
            }
            foreach ((array) ($checklist['checkItems'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                Ticket::create([
                    'project_id' => $ticket->project_id,
                    'number' => TicketNumbering::next($ticket->project_id),
                    'kind' => TicketKind::Dev, 'type' => 'subtask',
                    'title' => mb_substr((string) ($item['name'] ?? 'Checklist item'), 0, 200),
                    'status' => ($item['state'] ?? '') === 'complete' ? 'done' : 'todo',
                    'priority' => 'normal', 'reporter_id' => $ticket->reporter_id,
                    'requester_id' => $ticket->requester_id, 'parent_id' => $ticket->id,
                    'rank' => str_pad((string) random_int(1, 999999), 10, '0', STR_PAD_LEFT),
                ]);
                $counts['checklists']++;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  array<string, mixed>  $board
     * @param  Collection<string, mixed>  $memberMap
     * @param  array<string, int>  $counts
     */
    private function importComments(Ticket $ticket, array $card, array $board, int $actorId, Collection $memberMap, array &$counts): void
    {
        foreach ((array) ($board['actions'] ?? []) as $action) {
            if (! is_array($action) || ($action['type'] ?? '') !== 'commentCard') {
                continue;
            }
            $data = $action['data'] ?? [];
            if (! is_array($data) || (string) (($data['card']['id'] ?? '')) !== (string) ($card['id'] ?? '')) {
                continue;
            }
            $trelloMemberId = (string) (($action['idMemberCreator'] ?? ''));
            $userId = (int) ($memberMap->get($trelloMemberId) ?? $actorId);
            $prefix = '_Imported from Trello — '.(string) (($action['memberCreator']['fullName'] ?? 'Unknown').', '.($action['date'] ?? '')).'_\n';
            $comment = Comment::create([
                'ticket_id' => $ticket->id, 'user_id' => $userId,
                'body' => $prefix.strip_tags((string) ($data['text'] ?? '')), 'is_internal' => false,
            ]);
            ActivityLogger::log($ticket, 'imported_comment', null, null, null, User::find($userId));
            $counts['comments']++;
        }
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  array<string, string>  $credentials
     * @param  array<string, int>  $counts
     */
    private function importAttachments(Ticket $ticket, array $card, int $actorId, array $credentials, array &$counts, IntegrationConnection $connection): void
    {
        foreach ((array) ($card['attachments'] ?? []) as $attachment) {
            if (! is_array($attachment) || (string) ($attachment['isUpload'] ?? 'true') !== '1') {
                continue;
            }
            try {
                $url = (string) ($attachment['url'] ?? '');
                $contents = $this->client->downloadAttachment($url, $credentials);
                $path = tempnam(sys_get_temp_dir(), 'trello').'.'.pathinfo((string) ($attachment['name'] ?? 'file'), PATHINFO_EXTENSION);
                file_put_contents($path, $contents);
                $file = new UploadedFile($path, (string) ($attachment['name'] ?? 'file'), null, null, true);
                app(AddAttachment::class)($ticket, User::findOrFail($actorId), $file);
                unlink($path);
                $counts['attachments']++;
            } catch (\Throwable $exception) {
                $this->service->log($connection, 'warning', 'attachment_rejected', ['message' => $exception->getMessage()]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  array<string, mixed>  $board
     */
    private function importLabels(Ticket $ticket, array $card, array $board, IntegrationConnection $connection): void
    {
        $names = [];
        foreach ((array) ($card['idLabels'] ?? []) as $labelId) {
            foreach ((array) ($board['labels'] ?? []) as $label) {
                if (is_array($label) && (string) ($label['id'] ?? '') === (string) $labelId) {
                    $names[] = (string) ($label['name'] ?? '');
                }
            }
        }
        foreach (array_filter($names) as $name) {
            $label = Label::where('project_id', $ticket->project_id)->where('name', $name)->first();
            if ($label === null && (bool) config('trello.auto_create_labels')) {
                $label = Label::create(['project_id' => $ticket->project_id, 'name' => $name, 'color' => 'slate']);
            }
            if ($label !== null) {
                $ticket->labels()->syncWithoutDetaching([$label->id]);
            }
        }
    }
}
