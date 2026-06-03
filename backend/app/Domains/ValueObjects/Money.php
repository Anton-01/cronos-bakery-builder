<?php

declare(strict_types=1);

namespace App\Domains\ValueObjects;

use App\Domains\Enums\Currency;
use InvalidArgumentException;

/**
 * Immutable money value object shared across Catalog, Orders and Payments.
 * Amounts are stored as integer minor units (e.g. cents) to avoid floating
 * point rounding errors.
 */
final readonly class Money
{
    public function __construct(
        public int $amount,
        public Currency $currency = Currency::USD,
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }
    }

    public static function fromMajorUnits(float $value, Currency $currency = Currency::USD): self
    {
        return new self((int) round($value * 100), $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    public function toMajorUnits(): float
    {
        return $this->amount / 100;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot operate on different currencies.');
        }
    }
}
