<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeploymentAssetsTest extends TestCase
{
    public function test_docker_frontend_build_includes_tailwind_configuration(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertStringContainsString(
            'COPY postcss.config.js tailwind.config.js ./',
            $dockerfile,
        );
    }

    public function test_login_uses_same_origin_asset_paths(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('href="/build/assets/', false);
        $response->assertSee('src="/build/assets/', false);
    }
}
