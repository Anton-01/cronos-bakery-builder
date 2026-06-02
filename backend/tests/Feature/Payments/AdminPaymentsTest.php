<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\GatewayConfig;
use App\Modules\Payments\Domain\Models\Payment;
use App\Modules\Payments\Infrastructure\Jobs\RetryPaymentStatusJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsPaymentsAdmin(): void
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value); // has "manage payments"
        Sanctum::actingAs($admin);
    }

    public function test_an_admin_can_configure_a_gateway_and_switch_mode(): void
    {
        $this->actingAsPaymentsAdmin();

        $this->putJson('/api/admin/payments/gateways/openpay', [
            'mode' => 'production',
            'credentials' => ['secret_key' => 'sk_live', 'webhook_secret' => 'whsec_live'],
            'is_active' => true,
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.gateway', 'openpay')
            ->assertJsonPath('data.mode', 'production')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.has_webhook_secret', true);

        // Credentials are persisted (encrypted) but never returned in the payload.
        $config = GatewayConfig::query()->where('gateway', GatewayType::OpenPay->value)->firstOrFail();
        $this->assertSame('sk_live', $config->credential('secret_key'));
    }

    public function test_admin_can_list_gateways_and_payments(): void
    {
        $this->actingAsPaymentsAdmin();
        GatewayConfig::factory()->gateway(GatewayType::Stripe)->create();
        Payment::factory()->count(3)->create();

        $this->getJson('/api/admin/payments/gateways')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/payments')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_admin_can_queue_a_reconciliation_retry(): void
    {
        Queue::fake();
        $this->actingAsPaymentsAdmin();
        $payment = Payment::factory()->status(PaymentStatus::Pending)->create();

        $this->postJson("/api/admin/payments/{$payment->id}/retry")->assertOk();

        Queue::assertPushed(RetryPaymentStatusJob::class);
    }

    public function test_payments_admin_requires_permission(): void
    {
        $courier = Admin::factory()->create();
        $courier->assignRole(AdminRole::Courier->value);
        Sanctum::actingAs($courier);

        $this->getJson('/api/admin/payments/gateways')->assertForbidden();
    }

    public function test_the_retry_job_increments_attempts_for_non_final_payments(): void
    {
        $payment = Payment::factory()->status(PaymentStatus::Pending)->create(['attempts' => 0]);

        (new RetryPaymentStatusJob($payment->id))->handle(
            app(\App\Modules\Payments\Application\Services\ReconciliationService::class),
        );

        $this->assertSame(1, $payment->refresh()->attempts);
        $this->assertDatabaseHas('payment_events', ['payment_id' => $payment->id, 'type' => 'retry']);
    }

    public function test_the_retry_job_skips_finalised_payments(): void
    {
        $payment = Payment::factory()->status(PaymentStatus::Paid)->create(['attempts' => 0]);

        (new RetryPaymentStatusJob($payment->id))->handle(
            app(\App\Modules\Payments\Application\Services\ReconciliationService::class),
        );

        $this->assertSame(0, $payment->refresh()->attempts);
    }
}
