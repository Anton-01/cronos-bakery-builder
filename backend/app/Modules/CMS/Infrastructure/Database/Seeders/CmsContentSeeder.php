<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Seeders;

use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Enums\PageType;
use App\Modules\CMS\Domain\Models\Brand;
use App\Modules\CMS\Domain\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Seeds the default brand and its set of dynamic pages with sample builder
 * blocks so the frontend renders a complete site out of the box.
 */
class CmsContentSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::updateOrCreate(
            ['slug' => 'cronos-bakery'],
            ['name' => 'Cronos Bakery', 'is_active' => true],
        );

        $this->seedHome($brand);
        $this->seedSimplePage($brand, PageType::About, 'Nosotros', 'about');
        $this->seedSimplePage($brand, PageType::Contact, 'Contacto', 'contact');
        $this->seedFaq($brand);
        $this->seedSimplePage($brand, PageType::Policies, 'Políticas', 'policies');
        $this->seedSimplePage($brand, PageType::Blog, 'Blog', 'blog');
    }

    private function seedHome(Brand $brand): void
    {
        $home = Page::updateOrCreate(
            ['brand_id' => $brand->id, 'slug' => 'home'],
            [
                'title' => 'Inicio',
                'type' => PageType::Home->value,
                'meta_title' => 'Cronos Bakery — Pasteles artesanales a tu medida',
                'meta_description' => 'Diseña tu pastel personalizado y recíbelo fresco en tu puerta.',
                'status' => PageStatus::Published->value,
                'published_at' => now(),
            ],
        );

        $blocks = [
            [BlockType::Hero, [
                'heading' => 'Pasteles artesanales, hechos a tu manera',
                'subheading' => 'Diseña, ordena y disfruta.',
                'image' => '/images/hero.jpg',
                'cta_label' => 'Crear mi pastel',
                'cta_url' => '/builder',
            ]],
            [BlockType::Cards, [
                'title' => '¿Por qué Cronos?',
                'items' => [
                    ['title' => 'Ingredientes premium', 'text' => 'Solo lo mejor.'],
                    ['title' => 'Personalización total', 'text' => 'Tú decides cada detalle.'],
                    ['title' => 'Entrega puntual', 'text' => 'Del horno a tu puerta.'],
                ],
            ]],
            [BlockType::Products, [
                'title' => 'Nuestros favoritos',
                'source' => 'latest',
                'limit' => 8,
                'show_price' => true,
            ]],
            [BlockType::Testimonials, [
                'title' => 'Lo que dicen nuestros clientes',
                'items' => [
                    ['author' => 'María', 'quote' => '¡El mejor pastel que he probado!'],
                    ['author' => 'Carlos', 'quote' => 'Personalización increíble.'],
                ],
            ]],
            [BlockType::Cta, [
                'heading' => '¿Listo para tu próxima celebración?',
                'cta_label' => 'Empezar ahora',
                'cta_url' => '/builder',
            ]],
        ];

        // Para evitar duplicar bloques en la misma página, limpiamos los previos.
        $home->blocks()->delete();

        foreach ($blocks as $position => [$type, $data]) {
            $home->blocks()->create([
                'type' => $type->value,
                'data' => $data,
                'position' => $position,
            ]);
        }
    }

    private function seedFaq(Brand $brand): void
    {
        $faq = Page::updateOrCreate(
            ['brand_id' => $brand->id, 'slug' => 'faq'],
            [
                'title' => 'Preguntas frecuentes',
                'type' => PageType::Faq->value,
                'status' => PageStatus::Published->value,
                'published_at' => now(),
            ],
        );

        $faq->blocks()->delete();

        $faq->blocks()->create([
            'type' => BlockType::Faq->value,
            'position' => 0,
            'data' => [
                'title' => 'Preguntas frecuentes',
                'items' => [
                    ['question' => '¿Con cuánta anticipación debo ordenar?', 'answer' => 'Al menos 48 horas.'],
                    ['question' => '¿Hacen entregas?', 'answer' => 'Sí, en toda el área metropolitana.'],
                ],
            ],
        ]);
    }

    private function seedSimplePage(Brand $brand, PageType $type, string $title, string $slug): void
    {
        $page = Page::updateOrCreate(
            ['brand_id' => $brand->id, 'slug' => $slug],
            [
                'title' => $title,
                'type' => $type->value,
                'meta_title' => $title,
                'status' => PageStatus::Published->value,
                'published_at' => now(),
            ],
        );

        $page->blocks()->delete();

        $page->blocks()->create([
            'type' => BlockType::Text->value,
            'position' => 0,
            'data' => [
                'body' => "<p>Contenido de la página <strong>{$title}</strong>.</p>",
            ],
        ]);
    }
}
