<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Enums;

/**
 * How a dependency rule compares the source option's selected value(s) against
 * its expected value.
 */
enum RuleOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case In = 'in'; // expected value is a comma-separated list

    /**
     * Evaluate the operator against the value(s) currently selected for the
     * source option.
     *
     * @param  array<int, string>  $selectedValues
     */
    public function matches(string $expected, array $selectedValues): bool
    {
        return match ($this) {
            self::Equals => in_array($expected, $selectedValues, true),
            self::NotEquals => ! in_array($expected, $selectedValues, true),
            self::In => count(array_intersect(
                array_map('trim', explode(',', $expected)),
                $selectedValues,
            )) > 0,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $o): string => $o->value, self::cases());
    }
}
