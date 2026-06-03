<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Domain\Enums;

/**
 * Coarse-grained application roles. Fine-grained permissions can be layered on
 * top in the Administration module in a later phase.
 */
enum Role: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Staff => 'Staff',
            self::Admin => 'Administrator',
        };
    }
}
