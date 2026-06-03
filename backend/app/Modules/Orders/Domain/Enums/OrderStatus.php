<?php

declare(strict_types=1);

namespace App\Modules\Orders\Domain\Enums;

/**
 * Lifecycle of an order. Payment and production transitions are handled by
 * later phases; checkout creates orders as Pending.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case InProduction = 'in_production';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Confirmed => 'Confirmado',
            self::InProduction => 'En producción',
            self::Ready => 'Listo',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
