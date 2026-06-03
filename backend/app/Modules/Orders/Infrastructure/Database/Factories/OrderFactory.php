<?php

declare(strict_types=1);

namespace App\Modules\Orders\Infrastructure\Database\Factories;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Enums\FulfillmentType;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Domain\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'number' => 'CR-' . strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending->value,
            'fulfillment_type' => FulfillmentType::Pickup->value,
            'subtotal_amount' => 5000,
            'total_amount' => 5000,
            'currency' => 'USD',
            'placed_at' => now(),
        ];
    }
}
