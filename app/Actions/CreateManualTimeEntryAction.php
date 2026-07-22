<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\EntryStatus;
use App\Enums\EntryType;
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

class CreateManualTimeEntryAction
{
    public function __construct(
        private CalculateTimeEntryDurationService $duration,
        private TimeEntryConflictService $conflicts,
        private TimeEntryAuditService $audits,
    ) {}

    public function execute(User $actor, User $owner, Project $project, Activity $activity, array $data): TimeEntry
    {
        if ($owner->isNot($actor) && ! $actor->isManagerOrAdmin()) {
            throw ValidationException::withMessages(['user_id' => 'Você só pode lançar horas para si mesmo.']);
        }
        if (! $project->loadMissing('client')->isActive() || ! $activity->active) {
            throw ValidationException::withMessages(['project_id' => 'Cliente, projeto e atividade devem estar ativos.']);
        }

        $start = CarbonImmutable::parse($data['started_at'], config('app.timezone'));
        $end = CarbonImmutable::parse($data['ended_at'], config('app.timezone'));
        $seconds = $this->duration->calculate($start, $end);
        $now = CarbonImmutable::now(config('app.timezone'));
        if ($start->isFuture() || $end->isFuture()) {
            throw ValidationException::withMessages(['started_at' => 'Não é permitido registrar horas em datas futuras.']);
        }
        $maxDays = (int) Setting::valueOf('retroactive_entry_max_days', 30);
        if (! $actor->isAdmin() && $start->lt($now->subDays($maxDays))) {
            throw ValidationException::withMessages(['started_at' => "O limite para lançamentos retroativos é de {$maxDays} dias."]);
        }
        $maxHours = (int) Setting::valueOf('maximum_running_timer_hours', 24);
        if ($seconds > $maxHours * 3600) {
            throw ValidationException::withMessages(['ended_at' => "A duração máxima permitida é de {$maxHours} horas."]);
        }
        if ($this->conflicts->hasConflict($owner->id, $start, $end)) {
            throw ValidationException::withMessages(['started_at' => 'O período informado se sobrepõe a outro registro.']);
        }

        return DB::transaction(function () use ($actor, $owner, $project, $activity, $data, $start, $end, $seconds) {
            User::query()->whereKey($owner->id)->lockForUpdate()->firstOrFail();
            if ($this->conflicts->hasConflict($owner->id, $start, $end)) {
                throw ValidationException::withMessages(['started_at' => 'O período informado se sobrepõe a outro registro.']);
            }
            $needsApproval = Setting::bool('require_retroactive_approval', true);
            $entry = TimeEntry::create([
                'user_id' => $owner->id, 'project_id' => $project->id, 'activity_id' => $activity->id,
                'description' => $data['description'] ?? null, 'started_at' => $start, 'ended_at' => $end,
                'duration_seconds' => $seconds, 'entry_type' => EntryType::Manual,
                'status' => $needsApproval ? EntryStatus::Pending : EntryStatus::Approved,
                'created_by' => $actor->id, 'justification' => $data['justification'],
                'approved_by' => $needsApproval ? null : $actor->id,
                'approved_at' => $needsApproval ? null : now(),
            ]);
            $this->audits->record($entry, $actor, AuditAction::Created, null, $this->audits->snapshot($entry));

            return $entry;
        });
    }
}
