<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\EntryStatus;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\CalculateTimeEntryDurationService;
use App\Services\TimeEntryAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StopTimeEntryAction
{
    public function __construct(private CalculateTimeEntryDurationService $duration, private TimeEntryAuditService $audits) {}

    public function execute(TimeEntry $entry, User $actor): TimeEntry
    {
        return DB::transaction(function () use ($entry, $actor) {
            $entry = TimeEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ($entry->status !== EntryStatus::Running) {
                throw ValidationException::withMessages(['timer' => 'Este cronômetro não está em andamento.']);
            }
            $old = $this->audits->snapshot($entry);
            $endedAt = now();
            $entry->update([
                'ended_at' => $endedAt,
                'duration_seconds' => $this->duration->calculate($entry->started_at, $endedAt),
                'status' => EntryStatus::Completed,
            ]);
            $this->audits->record($entry, $actor, AuditAction::Updated, $old, $this->audits->snapshot($entry));

            return $entry;
        });
    }
}
