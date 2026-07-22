<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\EntryStatus;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntryAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectTimeEntryAction
{
    public function __construct(private TimeEntryAuditService $audits) {}

    public function execute(TimeEntry $entry, User $actor, string $reason): TimeEntry
    {
        if (blank($reason)) {
            throw ValidationException::withMessages(['rejection_reason' => 'Informe o motivo da rejeição.']);
        }

        return DB::transaction(function () use ($entry, $actor, $reason) {
            $entry = TimeEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ($entry->status !== EntryStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Apenas registros pendentes podem ser rejeitados.']);
            }
            $old = $this->audits->snapshot($entry);
            $entry->update(['status' => EntryStatus::Rejected, 'approved_by' => $actor->id, 'approved_at' => now(), 'rejection_reason' => $reason]);
            $this->audits->record($entry, $actor, AuditAction::Rejected, $old, $this->audits->snapshot($entry));

            return $entry;
        });
    }
}
