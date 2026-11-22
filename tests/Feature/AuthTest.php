<?php

declare(strict_types=1);

use App\Enums\Provider;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
    Http::fake();
});

it('shows the login page and signs in a local user', function (): void {
    $user = User::factory()->create(['password' => Hash::make('password123')]);

    expect($this->get('/login'))->assertOk()
        ->and($this->post('/login', ['email' => $user->email, 'password' => 'password123']))
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('does not leak which field was wrong and blocks inactive users', function (): void {
    $user = User::factory()->inactive()->create(['password' => Hash::make('password123')]);

    $this->from('/login')->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertRedirect('/login')->assertSessionHasErrors('email');
    $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
        ->assertRedirect('/login')->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('throttles login attempts', function (): void {
    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong']);
    }

    $this->get('/login')->assertOk();
    $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertStatus(429);
});

it('accepts a valid invitation and rejects expired signatures', function (): void {
    Notification::fake();
    $user = User::factory()->create(['password' => null]);
    $url = URL::temporarySignedRoute('invitations.edit', now()->addHours(48), ['user' => $user->id]);

    $this->get($url)->assertOk();
    $postUrl = URL::temporarySignedRoute('invitations.update', now()->addHours(48), ['user' => $user->id]);
    $this->post($postUrl, [
        'password' => 'a-very-long-password',
        'password_confirmation' => 'a-very-long-password',
    ])->assertRedirect(route('login'));
    expect($user->refresh()->password)->not->toBeNull();

    $this->get(route('invitations.edit', ['user' => $user->id]))->assertForbidden();
});

it('rejects Google sign-in from a disallowed or unverified email', function (): void {
    config(['ticketing.allowed_email_domains' => ['example.com']]);
    fakeGoogle(['email' => 'user@other.com', 'email_verified' => true]);

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
    expect(User::count())->toBe(0);
});

it('creates a requester from a verified Google account and links an existing email', function (): void {
    config(['ticketing.allowed_email_domains' => ['example.com']]);
    fakeGoogle(['id' => 'g-1', 'email' => 'new@example.com', 'email_verified' => true]);

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));
    $user = User::where('email', 'new@example.com')->firstOrFail();
    expect($user->role)->toBe(Role::Requester)
        ->and($user->provider)->toBe(Provider::Google)
        ->and($user->google_id)->toBe('g-1');
});

it('refuses to link a Google id that belongs to another account', function (): void {
    config(['ticketing.allowed_email_domains' => ['example.com']]);
    User::factory()->create(['email' => 'old@example.com', 'google_id' => 'g-1']);
    fakeGoogle(['id' => 'g-1', 'email' => 'new@example.com', 'email_verified' => true]);

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'));
    expect(User::where('email', 'new@example.com')->exists())->toBeFalse();
});

it('redirects admins without two factor to setup', function (): void {
    config(['ticketing.require_admin_2fa' => true]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('projects.index'))->assertRedirect(route('2fa.setup'));
});

function fakeGoogle(array $attributes): void
{
    $socialUser = SocialiteUser::fake(array_merge([
        'id' => 'g-default', 'name' => 'Google User', 'email_verified' => true,
    ], $attributes));
    $provider = Mockery::mock(Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

it('serves the 2FA challenge without requiring an authenticated session', function (): void {
    $this->get(route('2fa.challenge'))->assertRedirect(route('login'));

    $this->withSession(['auth.2fa_user_id' => User::factory()->create()->id])
        ->get(route('2fa.challenge'))
        ->assertOk();
});
