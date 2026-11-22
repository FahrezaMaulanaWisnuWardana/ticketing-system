<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

final class AuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array $metadata = [],
        ?User $actor = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => ($actor ?? auth('web')->user())?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip' => Request::ip(),
            'user_agent' => mb_substr((string) Request::userAgent(), 0, 500),
            'metadata' => self::redact($metadata),
        ]);
    }

    /** @param array<string, mixed> $metadata
     *  @return array<string, mixed> */
    private static function redact(array $metadata): array
    {
        foreach ($metadata as $key => $value) {
            if (in_array(strtolower((string) $key), ['password', 'token', 'secret', 'api_key', 'google_id', 'two_factor_secret'], true)) {
                $metadata[$key] = '[redacted]';
            }
        }

        return $metadata;
    }
}
