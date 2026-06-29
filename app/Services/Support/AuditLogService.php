<?php

namespace App\Services\Support;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Service;
use Illuminate\Database\Eloquent\Model;

class AuditLogService extends Service
{
    public function record(?User $actor, string $action, Model $entity, array $metadata = []): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'metadata_json' => $metadata,
            'created_at' => now(),
        ]);
    }
}
