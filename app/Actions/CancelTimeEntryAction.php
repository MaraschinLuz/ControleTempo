<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\EntryStatus;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntryAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelTimeEntryAction
{
    public function __construct(private TimeEntryAuditService $audits) {}

    public function execute(TimeEntry $entry, User $actor): TimeEntry
    {
        return DB::transaction(function () use ($entry, $actor) {
            $entry = TimeEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ($entry->status !== EntryStatus::Running) {
                throw ValidationException::withMessages(['timer' => 'Este cronômetro não está em andamento.']);
            }
            $old = $this->audits->snapshot($entry);
            $entry->update(['ended_at' => now(), 'duration_seconds' => 0, 'status' => EntryStatus::Cancelled]);
            $this->audits->record($entry, $actor, AuditAction::Deleted, $old, $this->audits->snapshot($entry));

            return $entry;
        });
    }
}
