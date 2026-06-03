<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Enums;

/**
 * Days of the week, aligned with Carbon's dayOfWeek (0 = Sunday).
 */
enum Weekday: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    public function label(): string
    {
        return match ($this) {
            self::Sunday => 'Domingo',
            self::Monday => 'Lunes',
            self::Tuesday => 'Martes',
            self::Wednesday => 'Miércoles',
            self::Thursday => 'Jueves',
            self::Friday => 'Viernes',
            self::Saturday => 'Sábado',
        };
    }

    /**
     * @return array<int, int>
     */
    public static function values(): array
    {
        return array_map(static fn (self $d): int => $d->value, self::cases());
    }
}
