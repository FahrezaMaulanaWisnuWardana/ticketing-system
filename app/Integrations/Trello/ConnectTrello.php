<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use App\Enums\TrelloConnectionStatus;
use App\Enums\TrelloMode;
use App\Enums\TrelloPrompt;
use App\Integrations\Contracts\IntegrationAdapter;
use App\Models\IntegrationConnection;
use App\Models\MemberMapping;
use App\Models\Project;
use App\Models\StatusMapping;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ConnectTrello
{
    private readonly IntegrationAdapter $adapter;

    public function __construct(?IntegrationAdapter $adapter = null)
    {
        $this->adapter = $adapter ?? app(IntegrationAdapter::class);
    }

    /** @param array<string, mixed> $data */
    public function __invoke(User $actor, Project $project, array $data): IntegrationConnection
    {
        $credentials = ['key' => $data['key'], 'token' => $data['token'], 'board_id' => $data['board_id']];
        try {
            $member = $this->adapter->testConnection($credentials);
            $board = $this->adapter->fetchBoard($data['board_id'], $credentials);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['credentials' => __('trello.invalid_credentials')]);
        }
        if (($member['id'] ?? '') === '' || ($board['id'] ?? '') === '') {
            throw ValidationException::withMessages(['credentials' => __('trello.invalid_credentials')]);
        }

        $mode = TrelloMode::from($data['mode'] ?? TrelloMode::ImportOnly->value);

        return DB::transaction(static function () use ($actor, $project, $data, $mode, $board): IntegrationConnection {
            $connection = IntegrationConnection::updateOrCreate(
                ['project_id' => $project->id, 'provider' => 'trello'],
                [
                    'created_by' => $actor->id,
                    'trello_board_id' => $data['board_id'],
                    'trello_board_name' => $board['name'] ?? null,
                    'api_key_encrypted' => $data['key'],
                    'token_encrypted' => $data['token'],
                    'mode' => $mode,
                    'status' => TrelloConnectionStatus::Active,
                    'last_error' => null,
                ],
            );

            foreach ([
                ['todo', 'todo'], ['in_progress', 'in_progress'], ['in_review', 'in_review'], ['done', 'done'],
            ] as [$listName, $status]) {
                foreach ((array) ($board['lists'] ?? []) as $list) {
                    if (! is_array($list) || ! str_contains(strtolower((string) ($list['name'] ?? '')), $listName)) {
                        continue;
                    }
                    StatusMapping::updateOrCreate(
                        ['connection_id' => $connection->id, 'trello_list_id' => (string) ($list['id'] ?? '')],
                        ['trello_list_name' => $list['name'] ?? null, 'ticket_status' => $status, 'direction' => 'both'],
                    );
                    break;
                }
            }

            foreach ((array) ($board['members'] ?? []) as $trelloMember) {
                if (! is_array($trelloMember)) {
                    continue;
                }
                MemberMapping::updateOrCreate(
                    ['connection_id' => $connection->id, 'trello_member_id' => (string) ($trelloMember['id'] ?? '')],
                    ['trello_username' => $trelloMember['username'] ?? null, 'user_id' => null],
                );
            }

            $project->forceFill(['trello_prompt' => TrelloPrompt::Connected, 'trello_prompt_at' => now()])->save();
            app(TrelloConnectionService::class)->log($connection, 'info', 'connected', ['board' => $data['board_id']]);

            return $connection;
        });
    }
}
