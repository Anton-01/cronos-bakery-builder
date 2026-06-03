<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Enums;

/**
 * The kinds of dynamic pages an administrator can create. The slug/route is
 * independent; the type drives default templates and navigation grouping.
 */
enum PageType: string
{
    case Home = 'home';
    case About = 'about';
    case Contact = 'contact';
    case Faq = 'faq';
    case Policies = 'policies';
    case Blog = 'blog';
    case Landing = 'landing';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Inicio',
            self::About => 'Nosotros',
            self::Contact => 'Contacto',
            self::Faq => 'FAQ',
            self::Policies => 'Políticas',
            self::Blog => 'Blog',
            self::Landing => 'Landing Page',
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
