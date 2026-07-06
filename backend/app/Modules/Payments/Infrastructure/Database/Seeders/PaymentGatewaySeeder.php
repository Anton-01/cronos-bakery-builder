<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Database\Seeders;

use App\Modules\Payments\Domain\Enums\GatewayEnvironment;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use Illuminate\Database\Seeder;

/**
 * Seeds one shared (brand-less) gateway instance per registered driver, in
 * sandbox with placeholder credentials. Stripe is active by default; brands
 * configure their own instances from administration.
 */
class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        foreach ((array) config('payments.drivers', []) as $driverName => $definition) {
            PaymentGateway::query()->updateOrCreate(
                ['driver_name' => $driverName, 'brand_id' => null],
                [
                    'name' => (string) ($definition['label'] ?? $driverName),
                    'environment' => GatewayEnvironment::Sandbox->value,
                    'credentials' => [
                        'public_key' => 'sandbox_public_key',
                        'secret_key' => 'sandbox_secret_key',
                        'webhook_secret' => 'sandbox_webhook_secret',
                    ],
                    'is_active' => $driverName === 'stripe',
                ],
            );
        }
    }
}
