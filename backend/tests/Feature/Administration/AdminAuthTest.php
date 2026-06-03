<?php

declare(strict_types=1);

namespace Tests\Feature\Administration;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(AdminRole $role): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole($role->value);

        return $admin;
    }

    public function test_an_admin_can_log_in_and_receive_a_token_with_roles(): void
    {
        $admin = $this->admin(AdminRole::Administrator);
        // The factory hashes the default "password".
        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['admin' => ['id', 'name', 'email', 'roles', 'permissions'], 'token'])
            ->assertJsonPath('admin.roles.0', AdminRole::Administrator->value);
    }

    public function test_inactive_admins_cannot_log_in(): void
    {
        $admin = Admin::factory()->inactive()->create();

        $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_a_customer_token_cannot_access_admin_routes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/me')->assertForbidden();
    }

    public function test_role_gated_access_control_enforces_roles(): void
    {
        // Access-control endpoints are gated to Super Admin / Administrador.
        Sanctum::actingAs($this->admin(AdminRole::Courier));
        $this->getJson('/api/admin/roles')->assertForbidden();

        Sanctum::actingAs($this->admin(AdminRole::Administrator));
        $this->getJson('/api/admin/roles')->assertOk();
    }

    public function test_super_admin_bypasses_all_permission_checks(): void
    {
        // Super Admin is not explicitly granted "manage users" but Gate::before
        // grants everything.
        Sanctum::actingAs($this->admin(AdminRole::SuperAdmin));

        $this->getJson('/api/admin/users')->assertOk();
    }

    public function test_permission_gated_route_blocks_roles_without_the_permission(): void
    {
        Sanctum::actingAs($this->admin(AdminRole::Courier));

        $this->getJson('/api/admin/users')->assertForbidden();
    }
}
