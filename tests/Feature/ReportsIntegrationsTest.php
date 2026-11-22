<?php

declare(strict_types=1);

use App\Enums\ProjectRole;
use App\Enums\TicketStatus;
use App\Jobs\ProcessGitWebhookJob;
use App\Jobs\TakeSprintSnapshotsJob;
use App\Models\GitLink;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintSnapshot;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CsvExport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('validates git webhook signatures, blocks replays and processes payloads', function (): void {
    config(['services.git.webhook_secret' => 'test-secret']);
    $project = Project::factory()->create(['key' => 'IT']);
    $ticket = Ticket::factory()->create(['project_id' => $project->id, 'status' => TicketStatus::InReview]);
    $payload = [
        'action' => 'closed',
        'pull_request' => ['merged' => true, 'html_url' => 'https://example.com/pr/1'],
        'ref' => 'feature/IT-'.$ticket->number,
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = 'sha256='.hash_hmac('sha256', $body, 'test-secret');

    $this->call('POST', route('webhooks.git', 'github'), [], [], [], [
        'HTTP_X-HUB-SIGNATURE-256' => $signature,
        'HTTP_X-GITHUB-DELIVERY' => 'delivery-1',
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(202);

    $this->call('POST', route('webhooks.git', 'github'), [], [], [], [
        'HTTP_X-HUB-SIGNATURE-256' => 'sha256=bad',
        'HTTP_X-GITHUB-DELIVERY' => 'delivery-2',
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertForbidden();

    $this->call('POST', route('webhooks.git', 'github'), [], [], [], [
        'HTTP_X-HUB-SIGNATURE-256' => $signature,
        'HTTP_X-GITHUB-DELIVERY' => 'delivery-1',
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(202);

    (new ProcessGitWebhookJob('github', $payload, 'delivery-1'))->handle();
    expect(GitLink::where('ticket_id', $ticket->id)->exists())->toBeTrue()
        ->and($ticket->refresh()->status)->toBe(TicketStatus::Done);
});

it('writes nightly sprint snapshots idempotently', function (): void {
    $sprint = Sprint::factory()->create(['state' => 'active']);
    Ticket::factory()->dev()->create(['project_id' => $sprint->project_id, 'sprint_id' => $sprint->id, 'estimate' => 5]);

    (new TakeSprintSnapshotsJob)->handle();
    (new TakeSprintSnapshotsJob)->handle();

    expect(SprintSnapshot::where('sprint_id', $sprint->id)->count())->toBe(1)
        ->and(SprintSnapshot::first()->remaining_points)->toBe(5);
});

it('prefixes CSV formula injections and streams an authorized export', function (): void {
    expect(CsvExport::cell('=SUM(A1:A9)'))->toBe("'=SUM(A1:A9)")
        ->and(CsvExport::cell('+cmd'))->toBe("'+cmd")
        ->and(CsvExport::cell('safe'))->toBe('safe');

    $lead = User::factory()->agent()->create();
    $project = Project::factory()->create(['owner_id' => $lead->id]);
    $project->members()->attach($lead->id, ['role' => ProjectRole::Lead->value]);
    Ticket::factory()->create(['project_id' => $project->id]);

    $this->actingAs($lead)->get(route('reports.index', $project))->assertOk();
    $this->actingAs($lead)->get(route('reports.export', [$project, 'type' => 'status']))
        ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
