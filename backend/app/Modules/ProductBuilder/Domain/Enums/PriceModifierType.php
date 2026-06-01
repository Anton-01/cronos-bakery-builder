<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Enums;

/**
 * How an option value affects the running price during configuration.
 */
enum PriceModifierType: string
{
    case None = 'none';
    case Add = 'add';        // Sumar precio
    case Subtract = 'subtract'; // Restar precio
    case Set = 'set';        // Modificar (fija) el precio base

    /**
     * Apply this modifier to a running total (amounts in minor units).
     */
    public function apply(int $current, int $amount): int
    {
        return match ($this) {
            self::None => $current,
            self::Add => $current + $amount,
            self::Subtract => max(0, $current - $amount),
            self::Set => $amount,
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
