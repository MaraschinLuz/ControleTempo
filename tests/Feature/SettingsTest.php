<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_manual_entries_for_collaborators(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Permitir que colaboradores adicionem horas manualmente');

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'retroactive_entry_max_days' => 30,
                'maximum_running_timer_hours' => 24,
                'require_retroactive_approval' => 1,
                'allow_collaborator_manual_entry' => 1,
                'allow_collaborator_edit' => 1,
                'allow_collaborator_delete' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key' => 'allow_collaborator_manual_entry',
            'value' => '1',
        ]);
    }
}
