<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryAuditService
{
    public function record(TimeEntry $entry, User $actor, AuditAction $action, ?array $old = null, ?array $new = null): void
    {
        $entry->audits()->create([
            'changed_by' => $actor->id,
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }

    public function snapshot(TimeEntry $entry): array
    {
        return collect($entry->getAttributes())->except(['created_at', 'updated_at', 'deleted_at'])->all();
    }
}
