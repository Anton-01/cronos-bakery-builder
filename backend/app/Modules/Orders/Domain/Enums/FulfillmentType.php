<?php

declare(strict_types=1);

namespace App\Modules\Orders\Domain\Enums;

/**
 * How an order is fulfilled: delivered to an address or picked up at a branch.
 */
enum FulfillmentType: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Delivery => 'Entrega a domicilio',
            self::Pickup => 'Recolección en sucursal',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $t): string => $t->value, self::cases());
    }
}
