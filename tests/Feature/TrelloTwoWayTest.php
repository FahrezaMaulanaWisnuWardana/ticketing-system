<?php

declare(strict_types=1);

use App\Enums\TicketStatus;
use App\Enums\TrelloExternalState;
use App\Enums\TrelloMode;
use App\Integrations\Trello\TrelloClient;
use App\Integrations\Trello\TrelloConnectionService;
use App\Integrations\Trello\TrelloException;
use App\Integrations\Trello\TrelloRateLimitException;
use App\Integrations\Trello\TrelloTwoWaySync;
use App\Integrations\Trello\TrelloWebhookHandler;
use App\Jobs\HandleTrelloWebhookJob;
use App\Jobs\ReconcileTrelloJob;
use App\Models\ExternalLink;
use App\Models\IntegrationLog;
use App\Models\StatusMapping;
use App\Models\SyncConflict;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('pushes app-only field changes to Trello without recording a conflict', function (): void {
    fakeTrello();
    [$lead, $project, $connection] = trelloSetup(TrelloMode::TwoWay);
    $ticket = Ticket::factory()->dev()->create(['project_id' => $project->id, 'title' => 'App title', 'status' => 'todo']);
    $link = ExternalLink::create([
        'ticket_id' => $ticket->id, 'provider' => 'trello', 'external_id' => 'card-1',
        'external_state' => TrelloExternalState::Ok,
        'field_hashes' => ['title' => hash('sha256', 'App title')],
    ]);

    $ticket->update(['title' => 'Changed in app']);
    app(TrelloTwoWaySync::class)->applyInbound($link->refresh(), ['title' => 'App title'], 'Alice');

    Http::assertSent(static fn ($request) => $request->method() === 'PUT'
        && str_contains($request->url(), '/cards/card-1')
        && ($request['name'] ?? null) === 'Changed in app');
    expect(SyncConflict::count())->toBe(0);
});

it('rejects invalid inbound transitions and flags the card', function (): void {
    fakeTrello();
    [$lead, $project, $connection] = trelloSetup(TrelloMode::TwoWay);
    $ticket = Ticket::factory()->dev()->create(['project_id' => $project->id, 'status' => 'done']);
    $link = ExternalLink::create([
        'ticket_id' => $ticket->id, 'provider' => 'trello', 'external_id' => 'card-1',
        'external_state' => TrelloExternalState::Ok,
        'field_hashes' => [],
    ]);

    // "Baru" is never a valid target from "Selesai" (reopen goes to "Dikerjakan").
    StatusMapping::where('trello_list_id', 'list-todo')->update(['ticket_status' => 'new']);
    app(TrelloTwoWaySync::class)->applyInbound($link, ['status' => 'list-todo'], 'Alice');

    expect($ticket->refresh()->status)->toBe(TicketStatus::Done)
        ->and($link->refresh()->external_state)->toBe(TrelloExternalState::ChangedExternally)
        ->and(IntegrationLog::where('event', 'invalid_inbound_transition')->exists())->toBeTrue();
});

it('marks archived and deleted cards without deleting the ticket', function (): void {
    fakeTrello();
    [$lead, $project, $connection] = trelloSetup(TrelloMode::Mirror);
    $ticket = Ticket::factory()->dev()->create(['project_id' => $project->id]);
    $link = ExternalLink::create([
        'ticket_id' => $ticket->id, 'provider' => 'trello', 'external_id' => 'card-1',
        'external_state' => TrelloExternalState::Ok,
    ]);
    $payload = trelloWebhookFixture();
    $payload['action']['type'] = 'deleteCard';
    (new HandleTrelloWebhookJob($connection->id, $payload))->handle(
        app(TrelloWebhookHandler::class), app(TrelloConnectionService::class),
    );

    expect($link->refresh()->external_state)->toBe(TrelloExternalState::Archived)
        ->and(Ticket::find($ticket->id))->not->toBeNull();
});

it('ignores webhook deliveries while the connection is paused', function (): void {
    fakeTrello();
    [$lead, $project, $connection] = trelloSetup(TrelloMode::TwoWay);
    $connection->forceFill(['status' => 'paused'])->save();
    $ticket = Ticket::factory()->dev()->create(['project_id' => $project->id, 'status' => 'todo']);
    ExternalLink::create([
        'ticket_id' => $ticket->id, 'provider' => 'trello', 'external_id' => 'card-1',
        'external_state' => TrelloExternalState::Ok,
    ]);

    (new HandleTrelloWebhookJob($connection->id, trelloWebhookFixture()))->handle(
        app(TrelloWebhookHandler::class), app(TrelloConnectionService::class),
    );

    expect($ticket->refresh()->status)->toBe(TicketStatus::Todo);
});

it('reconciles cards missed while webhooks were disabled', function (): void {
    fakeTrello();
    [$lead, $project, $connection] = trelloSetup(TrelloMode::TwoWay);
    $ticket = Ticket::factory()->dev()->create(['project_id' => $project->id, 'title' => 'Login page is broken']);

    (new ReconcileTrelloJob($connection->id))->handle(app(TrelloConnectionService::class));

    expect(ExternalLink::where('ticket_id', $ticket->id)->where('external_id', 'card-1')->exists())->toBeTrue();
});

it('never serializes credentials into logs or JSON responses and redacts log payloads', function (): void {
    [$lead, $project, $connection] = trelloSetup(TrelloMode::Mirror);
    app(TrelloConnectionService::class)->log($connection, 'info', 'test', ['token' => 'secret-token', 'key' => 'secret-key']);

    expect(json_encode($connection))->not->toContain('token')
        ->and(IntegrationLog::first()->payload)->not->toContain('secret-token');
});

it('pauses Trello calls after a 429 response', function (): void {
    config(['trello.retries' => 0]);
    Http::fake(['*' => Http::response([], 429)]);
    $client = app(TrelloClient::class);
    $credentials = ['key' => 'k', 'token' => 't429'];

    expect(fn () => $client->request('GET', '/members/me', $credentials))->toThrow(TrelloException::class);
    expect(fn () => $client->request('GET', '/members/me', $credentials))->toThrow(TrelloRateLimitException::class);
});
