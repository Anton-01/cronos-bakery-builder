<?php

declare(strict_types=1);

namespace Tests\Feature\Administration;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsAdministrator(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value);
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_the_dashboard_returns_aggregated_metrics(): void
    {
        $this->actingAsAdministrator();
        User::factory()->count(4)->create();
        $orders = Order::factory()->count(3)->create(['status' => OrderStatus::Confirmed->value]);
        Payment::factory()->status(PaymentStatus::Paid)
            ->create(['order_id' => $orders->first()->id, 'amount' => 5000, 'paid_at' => now()]);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'sales' => ['revenue', 'paid_payments', 'average_order_value'],
                'orders' => ['total', 'by_status'],
                'production' => ['in_production', 'ready', 'upcoming_pickups'],
                'conversion' => ['carts', 'orders', 'cart_to_order_rate'],
                'customers' => ['total', 'new', 'with_orders'],
            ]])
            ->assertJsonPath('data.sales.revenue', 5000)
            ->assertJsonPath('data.orders.total', 3);
    }

    public function test_every_admin_can_view_the_dashboard(): void
    {
        $courier = Admin::factory()->create();
        $courier->assignRole(AdminRole::Courier->value);
        Sanctum::actingAs($courier);

        $this->getJson('/api/admin/dashboard')->assertOk();
    }

    public function test_mutating_admin_actions_are_audited_automatically(): void
    {
        $admin = $this->actingAsAdministrator();

        // A create through any admin endpoint should be recorded.
        $this->postJson('/api/admin/cms/pages', [
            'title' => 'Audited Page', 'type' => 'landing', 'status' => 'draft',
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $admin->id,
            'method' => 'POST',
            'path' => '/api/admin/cms/pages',
            'status_code' => 201,
        ]);
    }

    public function test_read_only_admin_requests_are_not_audited(): void
    {
        $this->actingAsAdministrator();

        $this->getJson('/api/admin/dashboard')->assertOk();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_audit_payloads_redact_sensitive_fields(): void
    {
        $this->actingAsAdministrator();

        $this->putJson('/api/admin/payments/gateways/stripe', [
            'mode' => 'sandbox',
            'credentials' => ['secret_key' => 'sk_live_secret'],
            'is_active' => true,
        ])->assertSuccessful();

        $log = \App\Modules\Administration\Domain\Models\AuditLog::query()
            ->where('path', '/api/admin/payments/gateways/stripe')->firstOrFail();

        $this->assertSame('[redacted]', $log->payload['credentials'] ?? null);
    }

    public function test_an_admin_can_view_the_audit_log(): void
    {
        $this->actingAsAdministrator();
        $this->postJson('/api/admin/cms/sections', [
            'name' => 'X', 'type' => 'hero', 'data' => ['a' => 1],
        ])->assertCreated();

        $this->getJson('/api/admin/audit-logs')
            ->assertOk()
            ->assertJsonPath('data.0.path', '/api/admin/cms/sections');
    }

    public function test_an_admin_can_list_customers(): void
    {
        $this->actingAsAdministrator();
        User::factory()->create(['email' => 'jane@cronos.test', 'first_name' => 'Jane']);

        $this->getJson('/api/admin/users?search=jane')
            ->assertOk()
            ->assertJsonPath('data.0.email', 'jane@cronos.test');
    }

    public function test_an_admin_can_create_an_admin_and_assign_roles(): void
    {
        $this->actingAsAdministrator();

        $id = $this->postJson('/api/admin/admins', [
            'name' => 'New Staff',
            'email' => 'staff@cronos.test',
            'password' => 'Sup3r-Secret!',
            'password_confirmation' => 'Sup3r-Secret!',
            'roles' => [AdminRole::Production->value],
        ])->assertCreated()->assertJsonPath('data.roles.0', AdminRole::Production->value)->json('data.id');

        $this->putJson("/api/admin/admins/{$id}/roles", [
            'roles' => [AdminRole::Sales->value],
        ])->assertOk()->assertJsonPath('data.roles.0', AdminRole::Sales->value);
    }

    public function test_listing_roles_returns_the_permission_catalogue(): void
    {
        $this->actingAsAdministrator();

        $response = $this->getJson('/api/admin/roles')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonStructure(['data' => [['name', 'permissions']]]);

        $names = array_column($response->json('data'), 'name');
        $this->assertContains(AdminRole::Administrator->value, $names);
        $this->assertContains(AdminRole::Courier->value, $names);
    }

    public function test_guests_cannot_access_the_dashboard(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }
}
