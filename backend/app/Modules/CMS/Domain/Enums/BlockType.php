<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Enums;

/**
 * Configurable page-builder block types. Each block stores a free-form `data`
 * payload whose shape is interpreted by the matching frontend renderer.
 */
enum BlockType: string
{
    case Hero = 'hero';
    case Banner = 'banner';
    case Gallery = 'gallery';
    case Cards = 'cards';
    case Text = 'text';
    case Video = 'video';
    case Cta = 'cta';
    case Faq = 'faq';
    case Testimonials = 'testimonials';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::Banner => 'Banner',
            self::Gallery => 'Galería',
            self::Cards => 'Cards',
            self::Text => 'Texto',
            self::Video => 'Video',
            self::Cta => 'Call To Action',
            self::Faq => 'FAQ',
            self::Testimonials => 'Testimonios',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
