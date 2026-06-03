<?php

declare(strict_types=1);

namespace App\Modules\Orders\Infrastructure\Database\Factories;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Enums\AddressLabel;
use App\Modules\Orders\Domain\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => AddressLabel::Home->value,
            'recipient_name' => $this->faker->name(),
            'phone' => $this->faker->numerify('+506########'),
            'line1' => $this->faker->streetAddress(),
            'line2' => null,
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => 'CR',
            'notes' => null,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => ['is_default' => true]);
    }
}
