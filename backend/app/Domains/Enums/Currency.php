<?php

declare(strict_types=1);

namespace App\Domains\Enums;

/**
 * Currencies supported by the platform. Shared across every module that deals
 * with monetary values.
 */
enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case CRC = 'CRC';

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
            self::CRC => '₡',
        };
    }
}
