<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record an auditable action.
     *
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array $changes = [],
        array $metadata = [],
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'model_type' => $subject ? $subject->getMorphClass() : null,
            'model_id' => $subject?->getKey(),
            'changes' => $changes ?: null,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata ?: null,
        ]);
    }

    /** Record an action on a subject, snapshotting model changes. */
    public static function recordModelChange(string $action, Model $subject, array $changes = []): AuditLog
    {
        return self::record($action, $subject, $changes);
    }
}
