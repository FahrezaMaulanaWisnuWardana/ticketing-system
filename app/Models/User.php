<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Provider;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property Role $role
 * @property Provider $provider
 * @property bool $is_active
 * @property array<string, mixed> $notification_prefs
 * @property string|null $two_factor_secret
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'name', 'email', 'password', 'provider', 'google_id', 'role', 'is_active',
        'timezone', 'locale', 'two_factor_secret', 'two_factor_recovery_codes',
        'two_factor_confirmed_at', 'notification_prefs', 'last_login_at', 'email_verified_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'google_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'provider' => Provider::class,
            'role' => Role::class,
            'is_active' => 'bool',
            'notification_prefs' => 'array',
            'two_factor_recovery_codes' => 'array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isAgent(): bool
    {
        return $this->role === Role::Agent;
    }

    public function isRequester(): bool
    {
        return $this->role === Role::Requester;
    }

    public function hasTwoFactor(): bool
    {
        return $this->getAttributeFromArray('two_factor_secret') !== null
            && $this->getAttributeFromArray('two_factor_confirmed_at') !== null;
    }

    /** @return BelongsToMany<Project, $this, Membership, 'pivot'> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->using(Membership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Project, $this> */
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /** @return HasMany<Ticket, $this> */
    public function reportedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'reporter_id');
    }

    /** @return HasMany<Ticket, $this> */
    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    /** @return HasMany<Ticket, $this> */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    /** @return HasMany<Activity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function isMemberOf(Project $project): bool
    {
        return $this->projects()->where('projects.id', $project->id)->exists();
    }

    public function isLeadOf(Project $project): bool
    {
        return $this->projects()
            ->where('projects.id', $project->id)
            ->wherePivot('role', 'lead')
            ->exists();
    }
}
