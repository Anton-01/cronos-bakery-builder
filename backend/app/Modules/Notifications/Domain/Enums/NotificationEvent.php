<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Domain\Enums;

/**
 * Automation trigger keys. Templates are configured per event by the admin.
 */
enum NotificationEvent: string
{
    case OrderPlaced = 'order.placed';
    case PaymentApproved = 'payment.approved';
    case ProductionStarted = 'production.started';
    case OrderReady = 'order.ready';
    case OrderReminder = 'order.reminder';

    public function label(): string
    {
        return match ($this) {
            self::OrderPlaced => 'Compra realizada',
            self::PaymentApproved => 'Pago aprobado',
            self::ProductionStarted => 'Producción iniciada',
            self::OrderReady => 'Pedido listo',
            self::OrderReminder => 'Recordatorio',
        };
    }

    /**
     * Documented template variables available for this event.
     *
     * @return array<int, string>
     */
    public function variables(): array
    {
        return match ($this) {
            self::OrderReminder => ['customer_name', 'order_number', 'pickup_date', 'pickup_time', 'hours'],
            default => ['customer_name', 'order_number', 'total', 'status'],
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $e): string => $e->value, self::cases());
    }
}
