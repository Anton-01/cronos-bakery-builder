<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\CMS\Domain\Models\Brand;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use App\Modules\Payments\Domain\Models\Transaction;
use App\Modules\Payments\Infrastructure\Jobs\RetryPaymentStatusJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_an_admin_can_create_a_brand_scoped_gateway(): void
    {
        $this->actingAsPaymentsAdmin();
        $brand = Brand::factory()->create();

        $this->postJson('/api/admin/payments/gateways', [
            'brand_id' => $brand->id,
            'driver_name' => 'mercadopago',
            'name' => 'MercadoPago CR',
            'environment' => 'production',
            'is_active' => true,
            'credentials' => ['access_token' => 'APP_USR-live-token', 'webhook_secret' => 'whsec_live'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.driver_name', 'mercadopago')
            ->assertJsonPath('data.brand_id', $brand->id)
            ->assertJsonPath('data.environment', 'production')
            ->assertJsonPath('data.has_webhook_secret', true);
    }

    public function test_credentials_are_encrypted_at_rest_and_never_returned_in_plaintext(): void
    {
        $this->actingAsPaymentsAdmin();

        $response = $this->postJson('/api/admin/payments/gateways', [
            'driver_name' => 'stripe',
            'name' => 'Stripe',
            'environment' => 'sandbox',
            'credentials' => ['secret_key' => 'sk_live_SUPERSECRET1234', 'webhook_secret' => 'whsec_SECRET'],
        ])->assertCreated();

        // 1. API response only exposes masked hints, never the plaintext.
        $this->assertStringNotContainsString('sk_live_SUPERSECRET1234', $response->getContent());
        $this->assertSame('••••••••1234', $response->json('data.credentials.secret_key'));

        // 2. At rest the JSONB column holds encrypted values (plaintext keys).
        $raw = DB::table('payment_gateways')->value('credentials');
        $this->assertStringNotContainsString('sk_live_SUPERSECRET1234', (string) $raw);
        $this->assertStringContainsString('secret_key', (string) $raw);

        // 3. The cast decrypts transparently for backend consumers.
        $gateway = PaymentGateway::query()->firstOrFail();
        $this->assertSame('sk_live_SUPERSECRET1234', $gateway->credential('secret_key'));
    }

    public function test_updating_credentials_merges_only_provided_keys(): void
    {
        $this->actingAsPaymentsAdmin();
        $gateway = PaymentGateway::factory()->create([
            'credentials' => ['public_key' => 'pk_old', 'secret_key' => 'sk_old', 'webhook_secret' => 'wh_old'],
        ]);

        $this->putJson("/api/admin/payments/gateways/{$gateway->id}", [
            'credentials' => ['secret_key' => 'sk_new', 'webhook_secret' => null],
        ])->assertOk();

        $gateway->refresh();
        $this->assertSame('pk_old', $gateway->credential('public_key')); // preserved
        $this->assertSame('sk_new', $gateway->credential('secret_key')); // overwritten
        $this->assertNull($gateway->credential('webhook_secret')); // removed
    }

    public function test_duplicate_driver_per_brand_is_rejected_but_soft_deleted_rows_do_not_block(): void
    {
        $this->actingAsPaymentsAdmin();
        $gateway = PaymentGateway::factory()->create(); // stripe, brand null

        $payload = ['driver_name' => 'stripe', 'name' => 'Stripe 2', 'environment' => 'sandbox'];

        $this->postJson('/api/admin/payments/gateways', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('driver_name');

        $this->deleteJson("/api/admin/payments/gateways/{$gateway->id}")->assertNoContent();
        $this->assertSoftDeleted('payment_gateways', ['id' => $gateway->id]);

        $this->postJson('/api/admin/payments/gateways', $payload)->assertCreated();
    }

    public function test_gateway_listing_is_isolated_by_brand(): void
    {
        $this->actingAsPaymentsAdmin();
        $brandA = Brand::factory()->create();
        $brandB = Brand::factory()->create();
        PaymentGateway::factory()->create(['brand_id' => $brandA->id, 'name' => 'Stripe A']);
        PaymentGateway::factory()->driver('mercadopago')->create(['brand_id' => $brandB->id, 'name' => 'MP B']);
        PaymentGateway::factory()->driver('paypal')->create(['brand_id' => null, 'name' => 'Shared PayPal']);

        // Brand A sees its own gateway + the shared one, never brand B's.
        $names = collect($this->getJson("/api/admin/payments/gateways?brand_id={$brandA->id}")
            ->assertOk()->json('data'))->pluck('name');

        $this->assertEqualsCanonicalizing(['Stripe A', 'Shared PayPal'], $names->all());
    }

    public function test_admin_can_filter_transactions_by_status_and_date(): void
    {
        $this->actingAsPaymentsAdmin();
        $gateway = PaymentGateway::factory()->create();
        Transaction::factory()->status(PaymentStatus::Paid)->create([
            'payment_gateway_id' => $gateway->id, 'created_at' => '2026-07-01 10:00:00',
        ]);
        Transaction::factory()->status(PaymentStatus::Failed)->create([
            'payment_gateway_id' => $gateway->id, 'created_at' => '2026-07-01 11:00:00',
        ]);
        Transaction::factory()->status(PaymentStatus::Paid)->create([
            'payment_gateway_id' => $gateway->id, 'created_at' => '2026-05-01 10:00:00',
        ]);

        $this->getJson('/api/admin/payments/transactions?status=paid&date_from=2026-06-01&date_to=2026-07-31')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'paid');
    }

    public function test_admin_can_refund_a_paid_transaction_through_the_strategy(): void
    {
        $this->actingAsPaymentsAdmin();
        $transaction = Transaction::factory()->status(PaymentStatus::Paid)->create();

        $this->postJson("/api/admin/payments/transactions/{$transaction->id}/refund")
            ->assertOk()
            ->assertJsonPath('data.status', 'refunded');

        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id, 'type' => 'refund',
        ]);
    }

    public function test_refunding_a_non_paid_transaction_is_rejected(): void
    {
        $this->actingAsPaymentsAdmin();
        $transaction = Transaction::factory()->status(PaymentStatus::Pending)->create();

        $this->postJson("/api/admin/payments/transactions/{$transaction->id}/refund")->assertUnprocessable();
    }

    public function test_admin_can_queue_a_reconciliation_retry(): void
    {
        Queue::fake();
        $this->actingAsPaymentsAdmin();
        $transaction = Transaction::factory()->status(PaymentStatus::Pending)->create();

        $this->postJson("/api/admin/payments/transactions/{$transaction->id}/retry")->assertOk();

        Queue::assertPushed(RetryPaymentStatusJob::class);
    }

    public function test_payments_admin_requires_permission(): void
    {
        $courier = Admin::factory()->create();
        $courier->assignRole(AdminRole::Courier->value);
        Sanctum::actingAs($courier);

        $this->getJson('/api/admin/payments/gateways')->assertForbidden();
    }

    public function test_the_retry_job_increments_attempts_for_non_final_transactions(): void
    {
        $transaction = Transaction::factory()->status(PaymentStatus::Pending)->create(['attempts' => 0]);

        (new RetryPaymentStatusJob($transaction->id))->handle(
            app(\App\Modules\Payments\Application\Services\ReconciliationService::class),
        );

        $this->assertSame(1, $transaction->refresh()->attempts);
        $this->assertDatabaseHas('transaction_events', ['transaction_id' => $transaction->id, 'type' => 'retry']);
    }

    public function test_the_retry_job_skips_finalised_transactions(): void
    {
        $transaction = Transaction::factory()->status(PaymentStatus::Paid)->create(['attempts' => 0]);

        (new RetryPaymentStatusJob($transaction->id))->handle(
            app(\App\Modules\Payments\Application\Services\ReconciliationService::class),
        );

        $this->assertSame(0, $transaction->refresh()->attempts);
    }
}
