<?php

declare(strict_types=1);

namespace Tests\Feature\Production;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ObservabilityAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_health_endpoint_is_public_and_reports_checks(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'checks' => ['database', 'cache'], 'timestamp']);
    }

    public function test_security_headers_are_present_on_responses(): void
    {
        $response = $this->getJson('/api/status');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_metrics_require_an_authenticated_admin(): void
    {
        $this->getJson('/api/admin/metrics')->assertUnauthorized();
    }

    public function test_metrics_are_exposed_to_admins(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/metrics')
            ->assertOk()
            ->assertJsonStructure(['data' => ['queue', 'orders', 'payments', 'notifications']]);
    }

    public function test_admin_login_is_rate_limited(): void
    {
        // The login route is throttled at 6/min; the 7th attempt is blocked.
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/admin/login', ['email' => 'x@y.test', 'password' => 'bad']);
        }

        $this->postJson('/api/admin/login', ['email' => 'x@y.test', 'password' => 'bad'])
            ->assertStatus(429);
    }
}
