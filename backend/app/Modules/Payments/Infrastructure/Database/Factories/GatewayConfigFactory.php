<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Database\Factories;

use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Enums\PaymentMode;
use App\Modules\Payments\Domain\Models\GatewayConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GatewayConfig>
 */
class GatewayConfigFactory extends Factory
{
    protected $model = GatewayConfig::class;

    public function definition(): array
    {
        return [
            'gateway' => GatewayType::Stripe->value,
            'mode' => PaymentMode::Sandbox->value,
            'credentials' => [
                'public_key' => 'pk_test_x',
                'secret_key' => 'sk_test_x',
                'webhook_secret' => 'whsec_test_secret',
            ],
            'is_active' => true,
        ];
    }

    public function gateway(GatewayType $gateway): static
    {
        return $this->state(fn (array $attributes) => ['gateway' => $gateway->value]);
    }

    public function production(): static
    {
        return $this->state(fn (array $attributes) => ['mode' => PaymentMode::Production->value]);
    }
}
