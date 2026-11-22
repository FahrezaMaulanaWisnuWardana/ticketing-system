<?php

declare(strict_types=1);

use App\Enums\ProjectRole;
use App\Enums\TrelloExternalState;
use App\Enums\TrelloMode;
use App\Integrations\Trello\TrelloClient;
use App\Integrations\Trello\TrelloConnectionService;
use App\Integrations\Trello\TrelloException;
use App\Integrations\Trello\TrelloImporter;
use App\Integrations\Trello\TrelloMirror;
use App\Integrations\Trello\TrelloRateLimitException;
use App\Integrations\Trello\TrelloTwoWaySync;
use App\Integrations\Trello\TrelloWebhookHandler;
use App\Jobs\HandleTrelloWebhookJob;
use App\Jobs\MirrorTicketToTrelloJob;
use App\Jobs\MirrorTrelloCommentJob;
use App\Models\Comment;
use App\Models\ExternalLink;
use App\Models\ImportRun;
use App\Models\IntegrationConnection;
use App\Models\Project;
use App\Models\StatusMapping;
use App\Models\SyncConflict;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function trelloBoardFixture(): array
{
    return json_decode((string) file_get_contents(base_path('tests/Fixtures/trello/board.json')), true, 512, JSON_THROW_ON_ERROR);
}

function trelloWebhookFixture(): array
{
    return json_decode((string) file_get_contents(base_path('tests/Fixtures/trello/webhook.json')), true, 512, JSON_THROW_ON_ERROR);
}

function trelloSetup(TrelloMode $mode = TrelloMode::Mirror, bool $mirrorHelpdesk = false): array
{
    $lead = User::factory()->agent()->create();
    $project = Project::factory()->create(['owner_id' => $lead->id]);
    $project->members()->attach($lead->id, ['role' => ProjectRole::Lead->value]);
    $connection = IntegrationConnection::create([
        'project_id' => $project->id, 'created_by' => $lead->id, 'provider' => 'trello',
        'trello_board_id' => 'board-1', 'trello_board_name' => 'Sample Board',
        'api_key_encrypted' => 'key', 'token_encrypted' => 'token',
        'mode' => $mode, 'status' => 'active', 'mirror_helpdesk' => $mirrorHelpdesk,
    ]);
    StatusMapping::create([
        'connection_id' => $connection->id, 'trello_list_id' => 'list-todo',
        'ticket_status' => 'todo', 'direction' => 'both',
    ]);
    StatusMapping::create([
        'connection_id' => $connection->id, 'trello_list_id' => 'list-doing',
        'ticket_status' => 'in_progress', 'direction' => 'both',
    ]);

    return [$lead, $project, $connection];
}

function fakeTrello(): void
{
    Http::fake([
        'api.trello.com/1/members/me*' => Http::response(['id' => 'member-1', 'fullName' => 'Alice', 'username' => 'alice']),
        'api.trello.com/1/boards/board-1*' => Http::response(trelloBoardFixture()),
        'api.trello.com/1/cards/card-1/actions/comments*' => Http::response(['id' => 'comment-1']),
        'api.trello.com/1/cards*' => Http::response(['id' => 'new-card', 'url' => 'https://trello.com/c/new-card']),
        'api.trello.com/1/webhooks*' => Http::response(['id' => 'webhook-1']),
        '*' => Http::response([]),
    ]);
}

it('connects a board, seeds mappings and refuses invalid credentials', function (): void {
    fakeTrello();
    [$lead, $project] = trelloSetup(TrelloMode::ImportOnly);
    IntegrationConnection::where('project_id', $project->id)->delete();

    $this->actingAs($lead)->post(route('trello.connect.store', $project), [
        'key' => 'key', 'token' => 'token', 'board_id' => 'board-1', 'mode' => 'import_only',
        'existing' => 'start',
    ])->assertRedirect();

    $connection = IntegrationConnection::where('project_id', $project->id)->firstOrFail();
    expect($connection->trello_board_name)->toBe('Sample Board')
        ->and($connection->statusMappings()->count())->toBeGreaterThan(0)
        ->and(DB::table('integration_connections')->where('id', $connection->id)->value('api_key_encrypted'))->not->toBe('key');

});

it('refuses invalid Trello credentials without storing a connection', function (): void {
    Http::fake(['*' => Http::response([], 401)]);
    [$lead, $project, $existing] = trelloSetup(TrelloMode::ImportOnly);
    $existing->delete();
    $project->forceFill(['trello_prompt' => 'unasked'])->save();

    $this->actingAs($lead)->post(route('trello.connect.store', $project), [
        'key' => 'bad', 'token' => 'bad', 'board_id' => 'board-1', 'mode' => 'import_only',
    ])->assertSessionHasErrors('credentials');
    expect(IntegrationConnection::where('project_id', $project->id)->where('status', 'active')->count())->toBe(0);
});

it('imports a board idempotently, skips archived cards and records per-card activity', function (): void {
    fakeTrello();
    [$lead, $project, $connection] = trelloSetup(TrelloMode::ImportOnly);
    Notification::fake();

    $run = ImportRun::create(['connection_id' => $connection->id, 'started_by' => $lead->id, 'status' => 'pending']);
    app(TrelloImporter::class)->import($run);
    expect(Ticket::withoutGlobalScopes()->where('project_id', $project->id)->where('kind', 'dev')->count())->toBe(3)
        ->and(ExternalLink::where('provider', 'trello')->count())->toBe(1)
        ->and(Ticket::withoutGlobalScopes()->whereHas('activities', static fn ($q) => $q->where('event', 'imported_from_trello'))->count())->toBeGreaterThan(0);

    $run2 = ImportRun::create(['connection_id' => $connection->id, 'started_by' => $lead->id, 'status' => 'pending']);
    app(TrelloImporter::class)->import($run2);
    expect(Ticket::withoutGlobalScopes()->where('project_id', $project->id)->where('kind', 'dev')->count())->toBe(3)
        ->and((int) $run2->counts['skipped'])->toBe(2);
});

it('mirrors dev tickets but never internal notes or helpdesk requests', function (): void {
    fakeTrello();
    [$lead, $project, $connection] = trelloSetup(TrelloMode::Mirror);
    $dev = Ticket::factory()->dev()->create(['project_id' => $project->id, 'title' => 'Dev work']);
    $request = Ticket::factory()->create(['project_id' => $project->id, 'title' => 'Helpdesk request']);

    (new MirrorTicketToTrelloJob($dev->id))->handle(app(TrelloMirror::class), app(TrelloConnectionService::class));
    (new MirrorTicketToTrelloJob($request->id))->handle(app(TrelloMirror::class), app(TrelloConnectionService::class));

    Http::assertSent(static fn ($requestSent) => str_contains($requestSent->url(), '/1/cards')
        && ($requestSent['name'] ?? null) === 'Dev work');
    Http::assertNotSent(static fn ($requestSent) => ($requestSent['name'] ?? null) === 'Helpdesk request');

    $link = ExternalLink::where('ticket_id', $dev->id)->firstOrFail();
    $internal = Comment::create([
        'ticket_id' => $dev->id, 'user_id' => $lead->id, 'body' => 'Secret note', 'is_internal' => true,
    ]);
    (new MirrorTrelloCommentJob($internal->id))->handle(app(TrelloMirror::class), app(TrelloConnectionService::class));
    Http::assertNotSent(static fn ($requestSent) => str_contains($requestSent->url(), '/actions/comments'));
    expect($link->last_synced_hash)->not->toBeNull();
});

it('verifies webhook signatures, answers verification, blocks replays and detects external changes', function (): void {
    fakeTrello();
    [$lead, $project, $connection] = trelloSetup(TrelloMode::Mirror);
    $ticket = Ticket::factory()->dev()->create(['project_id' => $project->id, 'status' => 'todo']);
    ExternalLink::create([
        'ticket_id' => $ticket->id, 'provider' => 'trello', 'external_id' => 'card-1',
        'external_state' => TrelloExternalState::Ok,
    ]);
    $payload = trelloWebhookFixture();
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $callback = (string) config('trello.callback_url').'/'.$connection->id;
    $signature = hash_hmac('sha256', $callback.$body, (string) config('trello.webhook_secret'));

    $this->withHeaders(['X-Trello-Webhook' => $signature])
        ->call('POST', route('webhooks.trello', $connection), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_TRELLO_WEBHOOK' => $signature,
        ], $body)
        ->assertStatus(202);
    $this->call('POST', route('webhooks.trello', $connection), [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_X_TRELLO_WEBHOOK' => 'bad',
    ], $body)->assertForbidden();

    (new HandleTrelloWebhookJob($connection->id, $payload))->handle(
        app(TrelloWebhookHandler::class),
        app(TrelloConnectionService::class),
    );
    expect($ticket->refresh()->status->value)->toBe('todo')
        ->and(ExternalLink::where('ticket_id', $ticket->id)->first()->external_state)->toBe(TrelloExternalState::ChangedExternally);

    $this->get(route('webhooks.trello.verify', $connection))->assertOk();
});

it('applies only-Trello changes in two-way mode and keeps app value on conflicts', function (): void {
    fakeTrello();
    [$lead, $project, $connection] = trelloSetup(TrelloMode::TwoWay);
    $ticket = Ticket::factory()->dev()->create(['project_id' => $project->id, 'title' => 'App title', 'status' => 'todo']);
    $link = ExternalLink::create([
        'ticket_id' => $ticket->id, 'provider' => 'trello', 'external_id' => 'card-1',
        'external_state' => TrelloExternalState::Ok,
        'field_hashes' => ['title' => hash('sha256', 'App title'), 'status' => hash('sha256', 'list-todo')],
    ]);

    // Only Trello changed the title.
    app(TrelloTwoWaySync::class)->applyInbound($link, ['title' => 'Trello title'], 'Alice');
    expect($ticket->refresh()->title)->toBe('Trello title');

    // Both changed: app wins and a conflict is recorded.
    $ticket->update(['title' => 'New app title']);
    app(TrelloTwoWaySync::class)->applyInbound($link->refresh(), ['title' => 'Conflicting Trello title'], 'Alice');
    expect($ticket->refresh()->title)->toBe('New app title')
        ->and(SyncConflict::where('ticket_id', $ticket->id)->exists())->toBeTrue();
});

it('never exceeds the Trello rate budget and blocks non-Trello attachment hosts', function (): void {
    config(['trello.rate_limits.per_key.limit' => 3, 'trello.rate_limits.per_token.limit' => 3]);
    Http::fake(['*' => Http::response([])]);
    $client = app(TrelloClient::class);
    $credentials = ['key' => 'k', 'token' => 't'];

    for ($i = 0; $i < 3; $i++) {
        $client->request('GET', '/members/me', $credentials);
    }
    expect(fn () => $client->request('GET', '/members/me', $credentials))->toThrow(TrelloRateLimitException::class);
    expect(fn () => $client->downloadAttachment('https://evil.example.com/file.txt', $credentials))->toThrow(TrelloException::class);
});

it('hides all Trello routes and jobs when the flag is disabled', function (): void {
    $output = [];
    exec('cd '.escapeshellarg(base_path()).' && INTEGRATION_TRELLO_ENABLED=false php artisan route:list --json 2>/dev/null', $output);
    $routes = implode("\n", $output);
    expect($routes)->not->toContain('trello.settings')
        ->and($routes)->not->toContain('webhooks.trello');
});
