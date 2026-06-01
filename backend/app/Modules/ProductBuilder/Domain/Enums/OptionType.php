<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Enums;

/**
 * The input types a configurable option can render as. Choice-based types
 * (select/radio/checkbox/color/image) own a set of OptionValues; free-input
 * types (text/textarea) capture customer text.
 */
enum OptionType: string
{
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Color = 'color';
    case Image = 'image';
    case Text = 'text';
    case Textarea = 'textarea';

    /**
     * Whether this type is backed by a predefined list of values.
     */
    public function hasValues(): bool
    {
        return match ($this) {
            self::Text, self::Textarea => false,
            default => true,
        };
    }

    /**
     * Whether this type allows selecting more than one value.
     */
    public function isMultiple(): bool
    {
        return $this === self::Checkbox;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $t): string => $t->value, self::cases());
    }
}
