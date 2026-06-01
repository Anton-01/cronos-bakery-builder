<?php

declare(strict_types=1);

namespace App\Modules\Administration\Domain\Enums;

/**
 * The fixed set of administrative roles. Backed by Spatie roles created on the
 * `admin` guard (see RolesAndPermissionsSeeder).
 */
enum AdminRole: string
{
    case SuperAdmin = 'super-admin';
    case Administrator = 'administrator';
    case Production = 'production';
    case Sales = 'sales';
    case Marketing = 'marketing';
    case Courier = 'courier';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Administrator => 'Administrador',
            self::Production => 'Producción',
            self::Sales => 'Ventas',
            self::Marketing => 'Marketing',
            self::Courier => 'Repartidor',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
