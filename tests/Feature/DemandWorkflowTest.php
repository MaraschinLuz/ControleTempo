<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Demand;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_collaborator_sees_only_their_own_demand_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownDemand = Demand::factory()->for($user)->create(['title' => 'Minha demanda']);
        Demand::factory()->for($otherUser)->create(['title' => 'Demanda de outra pessoa']);

        $response = $this->actingAs($user)->get(route('demands.index', ['user_id' => $otherUser->id]));

        $response->assertOk()
            ->assertSee('Minha demanda')
            ->assertDontSee('Demanda de outra pessoa');
        $this->assertSame($user->id, $ownDemand->user_id);
    }

    public function test_manager_can_open_an_individual_user_board(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->create();
        Demand::factory()->for($user)->create(['title' => 'Demanda acompanhada']);

        $this->actingAs($manager)
            ->get(route('demands.index', ['user_id' => $user->id]))
            ->assertOk()
            ->assertSee("Quadro de {$user->name}")
            ->assertSee('Demanda acompanhada');
    }

    public function test_share_view_preserves_the_individual_board_and_print_context(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Projeto compartilhado']);
        Demand::factory()->for($user)->for($project)->create([
            'title' => 'Validar entrega com o cliente',
            'priority' => 'high',
        ]);

        $this->actingAs($manager)
            ->get(route('demands.share', ['user_id' => $user->id, 'project_id' => $project->id]))
            ->assertOk()
            ->assertSee('Resumo para compartilhar')
            ->assertSee($user->name)
            ->assertSee('Validar entrega com o cliente')
            ->assertSee('Projeto compartilhado')
            ->assertSee('Imprimir ou salvar em PDF');
    }

    public function test_demands_can_be_filtered_by_expected_completion_date(): void
    {
        $user = User::factory()->create();
        Demand::factory()->for($user)->create([
            'title' => 'Demanda da data selecionada',
            'due_date' => '2026-08-20',
        ]);
        Demand::factory()->for($user)->create([
            'title' => 'Demanda de outra data',
            'due_date' => '2026-08-21',
        ]);
        Demand::factory()->for($user)->create([
            'title' => 'Demanda sem data',
            'due_date' => null,
        ]);

        $this->actingAs($user)
            ->get(route('demands.index', ['due_date' => '2026-08-20']))
            ->assertOk()
            ->assertSee('Demanda da data selecionada')
            ->assertDontSee('Demanda de outra data')
            ->assertDontSee('Demanda sem data')
            ->assertSee('value="2026-08-20"', false);
    }

    public function test_collaborator_cannot_share_another_users_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Demand::factory()->for($user)->create(['title' => 'Demanda própria']);
        Demand::factory()->for($otherUser)->create(['title' => 'Demanda confidencial']);

        $this->actingAs($user)
            ->get(route('demands.share', ['user_id' => $otherUser->id]))
            ->assertOk()
            ->assertSee('Demanda própria')
            ->assertDontSee('Demanda confidencial');
    }

    public function test_collaborator_can_create_a_demand_with_project_and_priority(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for(Client::factory())->create();

        $response = $this->actingAs($user)->post(route('demands.store'), [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Preparar apresentação',
            'description' => 'Consolidar os resultados do projeto.',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'due_date' => '2026-08-10',
        ]);

        $response->assertRedirect(route('demands.index', ['user_id' => $user->id]));
        $this->assertDatabaseHas('demands', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Preparar apresentação',
            'status' => 'in_progress',
            'priority' => 'urgent',
        ]);
        $this->assertSame('2026-08-10', Demand::query()->firstOrFail()->due_date->format('Y-m-d'));
    }

    public function test_collaborator_cannot_create_a_demand_for_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)->post(route('demands.store'), [
            'user_id' => $otherUser->id,
            'project_id' => $project->id,
            'title' => 'Demanda indevida',
            'status' => 'pending',
            'priority' => 'medium',
        ])->assertForbidden();

        $this->assertDatabaseMissing('demands', ['title' => 'Demanda indevida']);
    }

    public function test_owner_can_move_a_demand_between_kanban_columns(): void
    {
        $user = User::factory()->create();
        $demand = Demand::factory()->for($user)->create(['status' => 'pending']);

        $this->actingAs($user)
            ->patchJson(route('demands.status', $demand), ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->assertDatabaseHas('demands', ['id' => $demand->id, 'status' => 'completed']);
    }

    public function test_collaborator_cannot_edit_or_delete_another_users_demand(): void
    {
        $user = User::factory()->create();
        $demand = Demand::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('demands.status', $demand), ['status' => 'completed'])
            ->assertForbidden();
        $this->actingAs($user)->delete(route('demands.destroy', $demand))->assertForbidden();

        $this->assertDatabaseHas('demands', ['id' => $demand->id, 'status' => 'pending']);
    }

    public function test_project_and_required_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('demands.index'))
            ->post(route('demands.store'), [
                'user_id' => $user->id,
                'project_id' => 999999,
                'title' => '',
                'status' => 'invalid',
                'priority' => 'invalid',
            ])
            ->assertRedirect(route('demands.index'))
            ->assertSessionHasErrors(['project_id', 'title', 'status', 'priority']);
    }
}
