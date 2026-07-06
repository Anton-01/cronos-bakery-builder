<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Database\Factories;

use App\Modules\Payments\Domain\Enums\GatewayEnvironment;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    protected $model = PaymentGateway::class;

    public function definition(): array
    {
        return [
            'brand_id' => null,
            'driver_name' => 'stripe',
            'name' => 'Stripe',
            'environment' => GatewayEnvironment::Sandbox->value,
            'credentials' => [
                'public_key' => 'pk_test_x',
                'secret_key' => 'sk_test_x',
                'webhook_secret' => 'whsec_test_secret',
            ],
            'is_active' => true,
        ];
    }

    public function driver(string $driverName): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_name' => $driverName,
            'name' => (string) config("payments.drivers.{$driverName}.label", $driverName),
        ]);
    }

    public function production(): static
    {
        return $this->state(fn (array $attributes) => ['environment' => GatewayEnvironment::Production->value]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
