<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

/**
 * Lifecycle of a payment.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Failed, self::Refunded, self::Cancelled], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Processing => 'Procesando',
            self::Paid => 'Pagado',
            self::Failed => 'Fallido',
            self::Refunded => 'Reembolsado',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
