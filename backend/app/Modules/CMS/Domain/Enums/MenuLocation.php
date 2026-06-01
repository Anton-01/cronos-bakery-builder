<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Enums;

/**
 * Where a dynamic menu is rendered in the storefront.
 */
enum MenuLocation: string
{
    case Header = 'header';
    case Footer = 'footer';

    public function label(): string
    {
        return match ($this) {
            self::Header => 'Encabezado',
            self::Footer => 'Pie de página',
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
