<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\CarbonImmutable;

class ActivityLogService
{
    public function log(
        int $schoolId,
        ?User $actor,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        array $meta = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        ActivityLog::create([
            'school_id' => $schoolId,
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'meta' => empty($meta) ? null : $meta,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
            'created_at' => CarbonImmutable::now(),
        ]);
    }
}
