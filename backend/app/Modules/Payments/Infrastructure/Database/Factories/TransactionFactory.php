<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Database\Factories;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use App\Modules\Payments\Domain\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'brand_id' => null,
            'order_id' => Order::factory(),
            'payment_gateway_id' => PaymentGateway::factory(),
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'currency' => 'USD',
            'provider_transaction_id' => 'ref_' . Str::random(10),
            'idempotency_key' => (string) Str::uuid(),
            'attempts' => 0,
        ];
    }

    public function status(PaymentStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status->value]);
    }
}
