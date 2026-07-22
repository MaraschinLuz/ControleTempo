<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\EntryStatus;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Setting;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\CalculateTimeEntryDurationService;
use App\Services\TimeEntryAuditService;
use App\Services\TimeEntryConflictService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTimeEntryAction
{
    public function __construct(
        private CalculateTimeEntryDurationService $duration,
        private TimeEntryConflictService $conflicts,
        private TimeEntryAuditService $audits,
    ) {}

    public function execute(TimeEntry $entry, User $actor, Project $project, Activity $activity, array $data): TimeEntry
    {
        if ($entry->status === EntryStatus::Running) {
            throw ValidationException::withMessages(['status' => 'Finalize ou cancele o cronômetro antes de editar o registro.']);
        }
        if (! $project->loadMissing('client')->isActive() || ! $activity->active) {
            throw ValidationException::withMessages(['project_id' => 'Cliente, projeto e atividade devem estar ativos.']);
        }
        $start = CarbonImmutable::parse($data['started_at'], config('app.timezone'));
        $end = CarbonImmutable::parse($data['ended_at'], config('app.timezone'));
        $seconds = $this->duration->calculate($start, $end);
        if ($end->isFuture()) {
            throw ValidationException::withMessages(['ended_at' => 'Não é permitido registrar horas em datas futuras.']);
        }
        $maxDays = (int) Setting::valueOf('retroactive_entry_max_days', 30);
        if (! $actor->isManagerOrAdmin() && $start->lt(CarbonImmutable::now()->subDays($maxDays))) {
            throw ValidationException::withMessages(['started_at' => "O prazo de edição é de {$maxDays} dias."]);
        }
        if ($this->conflicts->hasConflict($entry->user_id, $start, $end, $entry->id)) {
            throw ValidationException::withMessages(['started_at' => 'O período informado se sobrepõe a outro registro.']);
        }

        return DB::transaction(function () use ($entry, $actor, $project, $activity, $data, $start, $end, $seconds) {
            $entry = TimeEntry::query()->lockForUpdate()->findOrFail($entry->id);
            $old = $this->audits->snapshot($entry);
            $status = $entry->status;
            $approval = [];
            if (! $actor->isManagerOrAdmin() && Setting::bool('require_retroactive_approval', true)) {
                $status = EntryStatus::Pending;
                $approval = ['approved_by' => null, 'approved_at' => null, 'rejection_reason' => null];
            }
            $entry->update(array_merge([
                'project_id' => $project->id, 'activity_id' => $activity->id,
                'description' => $data['description'] ?? null, 'started_at' => $start, 'ended_at' => $end,
                'duration_seconds' => $seconds, 'justification' => $data['justification'] ?? $entry->justification,
                'status' => $status, 'is_edited' => true,
            ], $approval));
            $this->audits->record($entry, $actor, AuditAction::Updated, $old, $this->audits->snapshot($entry));

            return $entry;
        });
    }
}
