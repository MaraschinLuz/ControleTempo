<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Cliente Cronograma']);
        $this->project = Project::factory()->for($client)->create(['name' => 'Projeto Cronograma']);
    }

    public function test_guest_cannot_access_project_schedule(): void
    {
        $this->get(route('project-schedules.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_open_the_spreadsheet_for_a_project(): void
    {
        $this->actingAs($this->user)
            ->get(route('project-schedules.index', ['project_id' => $this->project->id]))
            ->assertOk()
            ->assertSee('Projeto Cronograma')
            ->assertSee('Column 1')
            ->assertSee('Sugestão IA')
            ->assertSee('Quantidade de horas')
            ->assertSee('Adicionar nova linha');
    }

    public function test_user_can_save_all_schedule_columns(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('project-schedules.update', $this->project),
            ['rows' => [[
                'column_1' => '1',
                'column_2' => '1.1',
                'demand' => 'Primeiro contato com o cliente',
                'ai_suggestion' => 'Primeiro contato',
                'completion_status' => 'Em andamento',
                'execution_date' => '2026-07-28',
                'responsible' => 'Pablo',
                'client_responsible' => 'Maria',
                'client_contact' => '(11) 99999-9999',
                'scope' => 'Tópicos do projeto',
                'completed_demands' => 'Contato realizado',
                'remaining_work' => 'Validar escopo',
                'completion_date' => '2026-07-31',
                'hours' => '4.50',
            ]]],
        );

        $response->assertRedirect(route('project-schedules.index', ['project_id' => $this->project->id]));
        $this->assertDatabaseHas('project_schedule_rows', [
            'project_id' => $this->project->id,
            'position' => 1,
            'column_1' => '1',
            'column_2' => '1.1',
            'demand' => 'Primeiro contato com o cliente',
            'completion_status' => 'Em andamento',
            'responsible' => 'Pablo',
            'hours' => 4.50,
        ]);
    }

    public function test_saving_again_removes_deleted_rows_and_reorders_the_others(): void
    {
        $this->actingAs($this->user)->put(
            route('project-schedules.update', $this->project),
            ['rows' => [
                ['demand' => 'Linha removida'],
                ['demand' => 'Linha mantida'],
            ]],
        );

        $this->actingAs($this->user)->put(
            route('project-schedules.update', $this->project),
            ['rows' => [['demand' => 'Linha mantida']]],
        )->assertSessionHas('success');

        $this->assertDatabaseCount('project_schedule_rows', 1);
        $this->assertDatabaseHas('project_schedule_rows', [
            'project_id' => $this->project->id,
            'position' => 1,
            'demand' => 'Linha mantida',
        ]);
        $this->assertDatabaseMissing('project_schedule_rows', ['demand' => 'Linha removida']);
    }

    public function test_schedule_rejects_invalid_status_and_negative_hours(): void
    {
        $this->actingAs($this->user)
            ->from(route('project-schedules.index', ['project_id' => $this->project->id]))
            ->put(route('project-schedules.update', $this->project), [
                'rows' => [[
                    'completion_status' => 'Talvez',
                    'hours' => -1,
                ]],
            ])
            ->assertSessionHasErrors(['rows.0.completion_status', 'rows.0.hours']);

        $this->assertDatabaseCount('project_schedule_rows', 0);
    }
}
