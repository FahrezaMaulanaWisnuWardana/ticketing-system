<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Services\MarkdownService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('verifies RFC 6238 codes and rejects wrong ones', function (): void {
    $service = new TotpService;
    $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    expect($service->verify('287082', $secret, 59))->toBeTrue()
        ->and($service->verify('000000', $secret, 59))->toBeFalse()
        ->and($service->verify('abc123', $secret, 59))->toBeFalse()
        ->and($service->generateRecoveryCodes())->toHaveCount(8);
});

it('strips raw html and unsafe links from markdown', function (): void {
    $service = new MarkdownService;
    $html = $service->render('# Hello <script>alert(1)</script> [x](javascript:alert(1)) [ok](https://example.com)');

    expect($html)->not->toContain('<script')
        ->and($html)->not->toContain('javascript:')
        ->and($html)->toContain('noopener')
        ->and($html)->toContain('noreferrer')
        ->and($html)->toContain('nofollow');
});

it('keeps audit logs and activities immutable', function (): void {
    $audit = AuditLog::create(['action' => 'test', 'metadata' => []]);
    $ticket = Ticket::factory()->create();
    $activity = Activity::create(['ticket_id' => $ticket->id, 'event' => 'created']);

    expect(static fn () => $audit->update(['action' => 'other']))->toThrow(LogicException::class)
        ->and(static fn () => $audit->delete())->toThrow(LogicException::class)
        ->and(static fn () => $activity->update(['event' => 'other']))->toThrow(LogicException::class)
        ->and(static fn () => $activity->delete())->toThrow(LogicException::class);
});
