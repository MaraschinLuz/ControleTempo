<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_render_the_icon_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Menu principal"', false)
            ->assertSee('Navegação')
            ->assertSee('Visão geral')
            ->assertSee('Adicionar horas')
            ->assertSee('Cronograma')
            ->assertSee('Demandas')
            ->assertSee('aria-current="page"', false);
    }

    public function test_add_time_page_has_only_its_own_navigation_item_active(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('time-entries.create'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'aria-current="page"'));
    }
}
