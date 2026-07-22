<?php

namespace Tests\Feature;

use App\Actions\ApproveTimeEntryAction;
use App\Actions\CreateManualTimeEntryAction;
use App\Actions\RejectTimeEntryAction;
use App\Actions\StartTimeEntryAction;
use App\Actions\StopTimeEntryAction;
use App\Actions\UpdateTimeEntryAction;
use App\Enums\EntryStatus;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Project;
use App\Models\Setting;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TimeEntryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $client = Client::factory()->create();
        $this->project = Project::factory()->for($client)->create(['status' => 'active']);
        $this->activity = Activity::factory()->create();
        foreach (['retroactive_entry_max_days' => '30', 'require_retroactive_approval' => '1', 'allow_collaborator_edit' => '1', 'allow_collaborator_delete' => '0', 'maximum_running_timer_hours' => '24'] as $key => $value) {
            Setting::create(compact('key', 'value'));
        }
        Cache::flush();
    }

    public function test_user_starts_a_timer(): void
    {
        $entry = app(StartTimeEntryAction::class)->execute($this->user, $this->project, $this->activity, 'Implementação');
        $this->assertSame(EntryStatus::Running, $entry->status);
        $this->assertNotNull($entry->started_at);
        $this->assertDatabaseHas('time_entry_audits', ['time_entry_id' => $entry->id, 'action' => 'created']);
    }

    public function test_user_stops_a_timer_and_server_calculates_duration(): void
    {
        $entry = $this->runningEntry(now()->subMinutes(75));
        $stopped = app(StopTimeEntryAction::class)->execute($entry, $this->user);
        $this->assertSame(EntryStatus::Completed, $stopped->status);
        $this->assertEqualsWithDelta(4500, $stopped->duration_seconds, 2);
    }

    public function test_user_cannot_start_two_timers(): void
    {
        $this->runningEntry();
        $this->expectException(ValidationException::class);
        app(StartTimeEntryAction::class)->execute($this->user, $this->project, $this->activity);
    }

    public function test_running_timer_is_recovered_after_page_reload(): void
    {
        $this->runningEntry(now()->subMinutes(10));
        $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertSee('EM ANDAMENTO')->assertSee($this->project->name);
    }

    public function test_valid_manual_entry_is_created_and_duration_is_calculated(): void
    {
        $entry = $this->manual('2026-07-20 09:00', '2026-07-20 11:30');
        $this->assertSame(9000, $entry->duration_seconds);
        $this->assertSame(EntryStatus::Pending, $entry->status);
        $this->assertSame('manual', $entry->entry_type->value);
    }

    public function test_end_before_start_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->manual('2026-07-20 11:00', '2026-07-20 09:00');
    }

    public function test_future_manual_entry_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->manual(now()->addHour()->format('Y-m-d H:i'), now()->addHours(2)->format('Y-m-d H:i'));
    }

    public function test_overlapping_entry_is_rejected(): void
    {
        $this->manual('2026-07-20 09:00', '2026-07-20 11:00');
        $this->expectException(ValidationException::class);
        $this->manual('2026-07-20 10:30', '2026-07-20 12:00');
    }

    public function test_entry_can_cross_midnight(): void
    {
        $entry = $this->manual('2026-07-20 22:00', '2026-07-21 01:30');
        $this->assertSame(12600, $entry->duration_seconds);
    }

    public function test_retroactive_day_limit_is_enforced(): void
    {
        Setting::where('key', 'retroactive_entry_max_days')->update(['value' => '2']);
        Cache::flush();
        $this->expectException(ValidationException::class);
        $this->manual(now()->subDays(3)->setTime(9, 0)->format('Y-m-d H:i'), now()->subDays(3)->setTime(10, 0)->format('Y-m-d H:i'));
    }

    public function test_admin_can_ignore_retroactive_day_limit(): void
    {
        Setting::where('key', 'retroactive_entry_max_days')->update(['value' => '2']);
        Cache::flush();
        $admin = User::factory()->admin()->create();
        $entry = $this->manual(now()->subDays(20)->setTime(9, 0)->format('Y-m-d H:i'), now()->subDays(20)->setTime(10, 0)->format('Y-m-d H:i'), $admin, $admin);
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);
    }

    public function test_collaborator_cannot_edit_another_users_entry(): void
    {
        $other = User::factory()->create();
        $entry = TimeEntry::factory()->for($other)->for($this->project)->for($this->activity)->create(['created_by' => $other->id]);
        $this->actingAs($this->user)->get(route('time-entries.edit', $entry))->assertForbidden();
    }

    public function test_manager_can_approve_pending_entry(): void
    {
        $entry = $this->manual('2026-07-20 09:00', '2026-07-20 10:00');
        $manager = User::factory()->manager()->create();
        app(ApproveTimeEntryAction::class)->execute($entry, $manager);
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id, 'status' => 'approved', 'approved_by' => $manager->id]);
    }

    public function test_manager_must_supply_reason_to_reject(): void
    {
        $entry = $this->manual('2026-07-20 09:00', '2026-07-20 10:00');
        $manager = User::factory()->manager()->create();
        try {
            app(RejectTimeEntryAction::class)->execute($entry, $manager, '');
            $this->fail('Validation expected');
        } catch (ValidationException) {
        }
        app(RejectTimeEntryAction::class)->execute($entry, $manager, 'Sem evidência da atividade.');
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id, 'status' => 'rejected', 'rejection_reason' => 'Sem evidência da atividade.']);
    }

    public function test_pending_hours_do_not_enter_approved_total(): void
    {
        $this->manual('2026-07-20 09:00', '2026-07-20 10:00');
        TimeEntry::factory()->for($this->user)->for($this->project)->for($this->activity)->create(['created_by' => $this->user->id, 'duration_seconds' => 7200]);
        $this->assertSame(7200, TimeEntry::query()->counted()->sum('duration_seconds'));
    }

    public function test_edit_recalculates_duration_and_writes_audit(): void
    {
        Setting::where('key', 'require_retroactive_approval')->update(['value' => '0']);
        Cache::flush();
        $entry = $this->manual('2026-07-20 09:00', '2026-07-20 10:00');
        app(UpdateTimeEntryAction::class)->execute($entry, $this->user, $this->project, $this->activity, ['started_at' => '2026-07-20 09:00', 'ended_at' => '2026-07-20 11:15', 'description' => 'Corrigido', 'justification' => 'Correção']);
        $this->assertSame(8100, $entry->refresh()->duration_seconds);
        $this->assertTrue($entry->is_edited);
        $this->assertDatabaseHas('time_entry_audits', ['time_entry_id' => $entry->id, 'action' => 'updated']);
    }

    public function test_deletion_respects_permissions(): void
    {
        $entry = TimeEntry::factory()->for($this->user)->for($this->project)->for($this->activity)->create(['created_by' => $this->user->id]);
        $this->actingAs($this->user)->delete(route('time-entries.destroy', $entry))->assertForbidden();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->delete(route('time-entries.destroy', $entry))->assertRedirect();
        $this->assertSoftDeleted($entry);
    }

    public function test_export_respects_filters(): void
    {
        $included = TimeEntry::factory()->for($this->user)->for($this->project)->for($this->activity)->create(['created_by' => $this->user->id, 'description' => 'INCLUIR']);
        TimeEntry::factory()->for($this->user)->for($this->project)->for($this->activity)->create(['created_by' => $this->user->id, 'status' => 'rejected', 'description' => 'EXCLUIR']);
        $response = $this->actingAs($this->user)->get(route('time-entries.export', ['status' => 'completed']));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('INCLUIR', $content);
        $this->assertStringNotContainsString('EXCLUIR', $content);
    }

    private function runningEntry($start = null): TimeEntry
    {
        return TimeEntry::create(['user_id' => $this->user->id, 'project_id' => $this->project->id, 'activity_id' => $this->activity->id, 'started_at' => $start ?? now(), 'duration_seconds' => 0, 'entry_type' => 'timer', 'status' => 'running', 'created_by' => $this->user->id]);
    }

    private function manual(string $start, string $end, ?User $actor = null, ?User $owner = null): TimeEntry
    {
        $actor ??= $this->user;
        $owner ??= $this->user;

        return app(CreateManualTimeEntryAction::class)->execute($actor, $owner, $this->project, $this->activity, ['started_at' => $start, 'ended_at' => $end, 'description' => 'Trabalho manual', 'justification' => 'Esqueci de iniciar o cronômetro.']);
    }
}
