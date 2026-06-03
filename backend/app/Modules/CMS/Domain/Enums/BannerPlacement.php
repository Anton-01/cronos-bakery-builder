<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Enums;

/**
 * Slots in which an administrable banner can be displayed.
 */
enum BannerPlacement: string
{
    case HomeTop = 'home_top';
    case HomeMiddle = 'home_middle';
    case Sidebar = 'sidebar';
    case CatalogTop = 'catalog_top';

    public function label(): string
    {
        return match ($this) {
            self::HomeTop => 'Inicio (superior)',
            self::HomeMiddle => 'Inicio (medio)',
            self::Sidebar => 'Barra lateral',
            self::CatalogTop => 'Catálogo (superior)',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $p): string => $p->value, self::cases());
    }
}
