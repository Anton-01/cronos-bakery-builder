<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Infrastructure\Database\Factories;

use App\Modules\Notifications\Domain\Enums\NotificationEvent;
use App\Modules\Notifications\Domain\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    public function definition(): array
    {
        return [
            'event' => NotificationEvent::OrderPlaced->value,
            'channel' => 'email',
            'subject' => 'Pedido {{ order_number }}',
            'body' => 'Hola {{ customer_name }}, tu pedido {{ order_number }} fue recibido.',
            'is_active' => true,
        ];
    }

    public function event(NotificationEvent $event): static
    {
        return $this->state(fn (array $attributes) => ['event' => $event->value]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
