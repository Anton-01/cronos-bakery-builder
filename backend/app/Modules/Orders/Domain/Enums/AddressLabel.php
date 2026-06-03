<?php

declare(strict_types=1);

namespace App\Modules\Orders\Domain\Enums;

/**
 * The kind of saved customer address.
 */
enum AddressLabel: string
{
    case Home = 'home';
    case Work = 'work';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Casa',
            self::Work => 'Trabajo',
            self::Other => 'Otra',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $l): string => $l->value, self::cases());
    }
}
