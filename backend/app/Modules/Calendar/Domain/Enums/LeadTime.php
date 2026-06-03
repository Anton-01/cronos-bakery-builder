<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Enums;

/**
 * Common production lead times (in hours). Stored as plain integers on rules,
 * but these presets cover the standard business options: 24h / 48h / 72h / 7d.
 */
enum LeadTime: int
{
    case Day = 24;
    case TwoDays = 48;
    case ThreeDays = 72;
    case Week = 168;

    public function label(): string
    {
        return match ($this) {
            self::Day => '24 horas',
            self::TwoDays => '48 horas',
            self::ThreeDays => '72 horas',
            self::Week => '7 días',
        };
    }

    /**
     * @return array<int, int>
     */
    public static function values(): array
    {
        return array_map(static fn (self $l): int => $l->value, self::cases());
    }
}
