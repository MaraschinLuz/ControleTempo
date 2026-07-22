<?php

namespace App\Services;

use App\Enums\EntryStatus;
use App\Models\TimeEntry;
use Carbon\CarbonInterface;

class TimeEntryConflictService
{
    public function hasConflict(int $userId, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null): bool
    {
        return TimeEntry::query()
            ->where('user_id', $userId)
            ->whereNotIn('status', [EntryStatus::Rejected->value, EntryStatus::Cancelled->value])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('started_at', '<', $end)
            ->where(fn ($query) => $query->whereNull('ended_at')->orWhere('ended_at', '>', $start))
            ->exists();
    }
}
