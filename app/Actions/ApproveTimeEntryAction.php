<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\EntryStatus;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntryAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveTimeEntryAction
{
    public function __construct(private TimeEntryAuditService $audits) {}

    public function execute(TimeEntry $entry, User $actor): TimeEntry
    {
        return DB::transaction(function () use ($entry, $actor) {
            $entry = TimeEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ($entry->status !== EntryStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Apenas registros pendentes podem ser aprovados.']);
            }
            $old = $this->audits->snapshot($entry);
            $entry->update(['status' => EntryStatus::Approved, 'approved_by' => $actor->id, 'approved_at' => now(), 'rejection_reason' => null]);
            $this->audits->record($entry, $actor, AuditAction::Approved, $old, $this->audits->snapshot($entry));

            return $entry;
        });
    }
}
