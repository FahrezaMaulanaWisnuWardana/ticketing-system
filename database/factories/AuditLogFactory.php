<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLog> */
final class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'actor_id' => null,
            'action' => 'login.success',
            'subject_type' => null,
            'subject_id' => null,
            'ip' => '127.0.0.1',
            'user_agent' => 'factory',
            'metadata' => [],
        ];
    }
}
