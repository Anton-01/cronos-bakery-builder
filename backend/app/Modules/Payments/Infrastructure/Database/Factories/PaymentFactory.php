<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Database\Factories;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Enums\PaymentMode;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'gateway' => GatewayType::Stripe->value,
            'mode' => PaymentMode::Sandbox->value,
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'currency' => 'USD',
            'reference' => 'ref_' . Str::random(10),
            'idempotency_key' => (string) Str::uuid(),
            'attempts' => 0,
        ];
    }

    public function status(PaymentStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status->value]);
    }
}
