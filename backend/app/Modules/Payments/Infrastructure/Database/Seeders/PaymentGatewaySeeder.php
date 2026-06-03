<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Database\Seeders;

use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Enums\PaymentMode;
use App\Modules\Payments\Domain\Models\GatewayConfig;
use Illuminate\Database\Seeder;

/**
 * Seeds the three payment gateways in sandbox mode with placeholder
 * credentials. Stripe is active by default; switch modes/credentials from
 * administration.
 */
class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [GatewayType::Stripe, true],
            [GatewayType::MercadoPago, false],
            [GatewayType::OpenPay, false],
        ];

        foreach ($gateways as [$gateway, $active]) {
            GatewayConfig::query()->updateOrCreate(
                ['gateway' => $gateway->value],
                [
                    'mode' => PaymentMode::Sandbox->value,
                    'credentials' => [
                        'public_key' => 'sandbox_public_key',
                        'secret_key' => 'sandbox_secret_key',
                        'webhook_secret' => 'sandbox_webhook_secret',
                    ],
                    'is_active' => $active,
                ],
            );
        }
    }
}
