<?php

namespace App\Services\Pos;

use App\Models\Pos\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PosAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        ?User $actor,
        string $event,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actor?->id,
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
