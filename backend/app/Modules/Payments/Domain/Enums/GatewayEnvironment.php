<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

/**
 * Operating environment for a gateway, switchable from administration.
 */
enum GatewayEnvironment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';

    public function label(): string
    {
        return match ($this) {
            self::Sandbox => 'Sandbox',
            self::Production => 'Producción',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $m): string => $m->value, self::cases());
    }
}
