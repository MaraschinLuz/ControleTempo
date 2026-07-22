<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardUserIndicatorsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_admin_sees_indicators_grouped_by_active_user(): void
    {
        CarbonImmutable::setTestNow('2026-07-22 12:00:00');

        $admin = User::factory()->admin()->create(['name' => 'Administrador']);
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        User::factory()->create(['name' => 'Usuário inativo', 'active' => false]);
        $project = Project::factory()->for(Client::factory())->create();
        $activity = Activity::factory()->create();

        $this->entry($alice, $project, $activity, '2026-07-22 09:00:00', 3600);
        $this->entry($alice, $project, $activity, '2026-07-20 09:00:00', 7200);
        $this->entry($alice, $project, $activity, '2026-07-05 09:00:00', 1800);
        $this->entry($alice, $project, $activity, '2026-07-21 09:00:00', 900, 'pending');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()->assertSee('Indicadores por usuário')->assertDontSee('Usuário inativo');
        $response->assertViewHas('userIndicators', function ($rows) use ($alice, $bob) {
            $aliceTotals = $rows->firstWhere('id', $alice->id);
            $bobTotals = $rows->firstWhere('id', $bob->id);

            return (int) $aliceTotals->today_seconds === 3600
                && (int) $aliceTotals->week_seconds === 10800
                && (int) $aliceTotals->month_seconds === 12600
                && (int) $aliceTotals->pending_seconds === 900
                && (int) ($bobTotals->today_seconds ?? 0) === 0;
        });
    }

    public function test_collaborator_cannot_see_other_users_indicators(): void
    {
        $collaborator = User::factory()->create();
        User::factory()->create(['name' => 'Outro usuário']);

        $this->actingAs($collaborator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Indicadores por usuário')
            ->assertViewHas('userIndicators', fn ($rows) => $rows->isEmpty());
    }

    private function entry(
        User $user,
        Project $project,
        Activity $activity,
        string $startedAt,
        int $duration,
        string $status = 'completed',
    ): TimeEntry {
        return TimeEntry::factory()
            ->for($user)
            ->for($project)
            ->for($activity)
            ->create([
                'created_by' => $user->id,
                'started_at' => $startedAt,
                'ended_at' => CarbonImmutable::parse($startedAt)->addSeconds($duration),
                'duration_seconds' => $duration,
                'status' => $status,
            ]);
    }
}
