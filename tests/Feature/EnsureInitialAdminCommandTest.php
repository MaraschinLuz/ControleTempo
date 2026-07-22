<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnsureInitialAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_initial_admin_from_deployment_configuration(): void
    {
        config()->set('deployment.admin', [
            'name' => 'Admin Produção',
            'email' => 'admin@empresa.com',
            'password' => 'uma-senha-segura-123',
        ]);

        $this->artisan('app:ensure-admin')->assertSuccessful();

        $user = User::where('email', 'admin@empresa.com')->firstOrFail();
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->active);
        $this->assertTrue(Hash::check('uma-senha-segura-123', $user->password));
    }

    public function test_it_rejects_a_short_admin_password(): void
    {
        config()->set('deployment.admin', [
            'name' => 'Admin',
            'email' => 'admin@empresa.com',
            'password' => 'curta',
        ]);

        $this->artisan('app:ensure-admin')->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'admin@empresa.com']);
    }
}
