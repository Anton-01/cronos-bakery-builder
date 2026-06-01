<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Enums;

/**
 * What a dependency rule does to its target option when the condition matches.
 */
enum RuleAction: string
{
    case Show = 'show';
    case Hide = 'hide';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $a): string => $a->value, self::cases());
    }
}
