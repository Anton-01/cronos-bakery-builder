<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Infrastructure\Database\Seeders;

use App\Modules\Notifications\Domain\Enums\NotificationEvent;
use App\Modules\Notifications\Domain\Models\NotificationTemplate;
use App\Modules\Notifications\Domain\Models\ReminderRule;
use Illuminate\Database\Seeder;

/**
 * Seeds the default email templates per event and the standard reminder rules
 * (24h / 12h / 2h before pickup).
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [NotificationEvent::OrderPlaced, 'Recibimos tu pedido {{ order_number }}',
                '<p>Hola {{ customer_name }}, recibimos tu pedido <strong>{{ order_number }}</strong> por {{ total }}. ¡Gracias!</p>'],
            [NotificationEvent::PaymentApproved, 'Pago aprobado — {{ order_number }}',
                '<p>Hola {{ customer_name }}, tu pago del pedido {{ order_number }} fue aprobado.</p>'],
            [NotificationEvent::ProductionStarted, 'Tu pedido {{ order_number }} está en producción',
                '<p>¡Manos a la obra! Tu pedido {{ order_number }} ya está en producción.</p>'],
            [NotificationEvent::OrderReady, 'Tu pedido {{ order_number }} está listo',
                '<p>Hola {{ customer_name }}, tu pedido {{ order_number }} está listo.</p>'],
            [NotificationEvent::OrderReminder, 'Recordatorio: recoge tu pedido {{ order_number }}',
                '<p>Hola {{ customer_name }}, te recordamos recoger tu pedido {{ order_number }} el {{ pickup_date }} a las {{ pickup_time }} (en {{ hours }}h).</p>'],
        ];

        foreach ($templates as [$event, $subject, $body]) {
            NotificationTemplate::query()->updateOrCreate(
                ['event' => $event->value],
                ['channel' => 'email', 'subject' => $subject, 'body' => $body, 'is_active' => true],
            );
        }

        foreach ([24, 12, 2] as $hours) {
            ReminderRule::query()->updateOrCreate(['offset_hours' => $hours], ['is_active' => true]);
        }
    }
}
