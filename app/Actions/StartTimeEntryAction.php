<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\EntryStatus;
use App\Enums\EntryType;
use App\Models\Activity;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntryAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartTimeEntryAction
{
    public function __construct(private TimeEntryAuditService $audits) {}

    public function execute(User $user, Project $project, Activity $activity, ?string $description = null): TimeEntry
    {
        if (! $project->loadMissing('client')->isActive()) {
            throw ValidationException::withMessages(['project_id' => 'Selecione um projeto e cliente ativos.']);
        }
        if (! $activity->active) {
            throw ValidationException::withMessages(['activity_id' => 'Selecione uma atividade ativa.']);
        }

        return DB::transaction(function () use ($user, $project, $activity, $description) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (TimeEntry::query()->where('user_id', $user->id)->where('status', EntryStatus::Running)->exists()) {
                throw ValidationException::withMessages([
                    'timer' => 'Você já possui uma atividade em andamento. Finalize ou cancele o registro atual antes de iniciar outro.',
                ]);
            }

            $entry = TimeEntry::create([
                'user_id' => $user->id, 'project_id' => $project->id, 'activity_id' => $activity->id,
                'description' => $description, 'started_at' => now(), 'duration_seconds' => 0,
                'entry_type' => EntryType::Timer, 'status' => EntryStatus::Running, 'created_by' => $user->id,
            ]);
            $this->audits->record($entry, $user, AuditAction::Created, null, $this->audits->snapshot($entry));

            return $entry;
        });
    }
}
