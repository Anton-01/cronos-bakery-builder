<?php

declare(strict_types=1);

namespace Tests\Feature\Administration;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Authentication\Domain\Models\User;
use App\Modules\CMS\Domain\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSessionsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value);
        Sanctum::actingAs($admin);
    }

    public function test_an_admin_can_audit_a_users_recent_sessions(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api');
        $token->accessToken->forceFill([
            'ip_address' => '200.10.20.30',
            'user_agent' => 'Mozilla/5.0 (iPhone) Safari/604.1',
            'device_name' => 'Safari · iPhone',
        ])->save();

        $this->getJson("/api/admin/users/{$user->id}/sessions")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ip_address', '200.10.20.30')
            ->assertJsonPath('data.0.device_name', 'Safari · iPhone');
    }

    public function test_users_can_be_filtered_by_brand(): void
    {
        $brandA = Brand::factory()->create();
        $brandB = Brand::factory()->create();
        User::factory()->create(['brand_id' => $brandA->id, 'email' => 'a@brand-a.test']);
        User::factory()->create(['brand_id' => $brandB->id, 'email' => 'b@brand-b.test']);
        User::factory()->create(['brand_id' => null, 'email' => 'global@none.test']);

        $this->getJson("/api/admin/users?brand_id={$brandA->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'a@brand-a.test')
            ->assertJsonPath('data.0.brand_id', $brandA->id);
    }

    public function test_customer_login_records_client_context(): void
    {
        $user = User::factory()->create(['password' => 'Secret1234']);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh; Mac OS X) Firefox/128.0'])
            ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'Secret1234'])
            ->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'device_name' => 'Firefox · macOS',
        ]);
    }
}
