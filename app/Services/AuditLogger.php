<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Append an audit record. Records are never updated or deleted by the application.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        ?Model $entity = null,
        array $metadata = [],
        ?User $actor = null,
        ?int $actorId = null,
    ): AuditLog {
        $actor ??= Auth::user();
        $actorId ??= $actor?->getKey();

        return AuditLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
        ]);
    }
}
