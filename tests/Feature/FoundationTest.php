<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('adds security headers and a report-only CSP by default', function (): void {
    config(['ticketing.csp_mode' => 'report-only']);

    $this->get('/login')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Content-Security-Policy-Report-Only')
        ->assertHeader('X-Request-Id');
});

it('registers request ids and correlates them', function (): void {
    $response = $this->withHeader('X-Request-Id', 'test-request-1')->get('/login');
    $response->assertHeader('X-Request-Id', 'test-request-1');
});

it('lets admins invite, change roles and deactivate users with an audit trail', function (): void {
    Http::preventStrayRequests();
    Http::fake();
    Notification::fake();
    $admin = User::factory()->admin()->create(['password' => Hash::make('password123')]);

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New Agent', 'email' => 'agent@example.com', 'role' => 'agent',
    ])->assertRedirect(route('admin.users.index'));

    $invited = User::where('email', 'agent@example.com')->firstOrFail();
    expect($invited->password)->toBeNull()
        ->and($invited->role->value)->toBe('agent')
        ->and($admin->auditLogs()->where('action', 'invite.sent')->exists())->toBeTrue();

    $this->actingAs($admin)->put(route('admin.users.update', $invited), ['role' => 'admin'])->assertRedirect();
    expect($invited->refresh()->isAdmin())->toBeTrue();

    $this->actingAs($admin)->post(route('admin.users.deactivate', $invited))->assertRedirect();
    expect($invited->refresh()->is_active)->toBeFalse();
});

it('ships all UI strings in both languages', function (): void {
    foreach (['auth', 'common', 'roles', 'project_roles', 'status', 'tickets', 'projects', 'priorities', 'admin', 'mail', 'notifications', 'agent', 'canned', 'search', 'sla', 'requester'] as $group) {
        $id = require lang_path('id/'.$group.'.php');
        $en = require lang_path('en/'.$group.'.php');
        expect($id)->toBeArray()->and($en)->toBeArray();
        foreach (array_keys($en) as $key) {
            expect(array_key_exists($key, $id), "Missing id translation for {$group}.{$key}")->toBeTrue();
        }
    }
});

it('keeps migrations reversible', function (): void {
    $path = database_path('migrations');
    $files = glob($path.'/*.php');
    expect(count($files))->toBeGreaterThan(5);
    foreach ($files as $file) {
        $contents = (string) file_get_contents($file);
        expect(str_contains($contents, 'function up()'), "Missing up() in {$file}")->toBeTrue()
            ->and(str_contains($contents, 'function down()'), "Missing down() in {$file}")->toBeTrue();
    }
});
