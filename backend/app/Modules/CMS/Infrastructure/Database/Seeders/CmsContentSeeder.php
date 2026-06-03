<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Seeders;

use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Enums\PageType;
use App\Modules\CMS\Domain\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Seeds the default set of dynamic pages with sample builder blocks so the
 * frontend renders a complete site out of the box.
 */
class CmsContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHome();
        $this->seedSimplePage(PageType::About, 'Nosotros', 'about');
        $this->seedSimplePage(PageType::Contact, 'Contacto', 'contact');
        $this->seedFaq();
        $this->seedSimplePage(PageType::Policies, 'Políticas', 'policies');
        $this->seedSimplePage(PageType::Blog, 'Blog', 'blog');
    }

    private function seedHome(): void
    {
        $home = Page::factory()->published()->ofType(PageType::Home)->create([
            'title' => 'Inicio',
            'slug' => 'home',
            'meta_title' => 'Cronos Bakery — Pasteles artesanales a tu medida',
            'meta_description' => 'Diseña tu pastel personalizado y recíbelo fresco en tu puerta.',
        ]);

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

        foreach ($blocks as $position => [$type, $data]) {
            $home->sections()->create([
                'type' => $type->value,
                'data' => $data,
                'position' => $position,
            ]);
        }
    }

    private function seedFaq(): void
    {
        $faq = Page::factory()->published()->ofType(PageType::Faq)->create([
            'title' => 'Preguntas frecuentes',
            'slug' => 'faq',
        ]);

        $faq->sections()->create([
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

    private function seedSimplePage(PageType $type, string $title, string $slug): void
    {
        $page = Page::factory()->published()->ofType($type)->create([
            'title' => $title,
            'slug' => $slug,
            'status' => PageStatus::Published->value,
        ]);

        $page->sections()->create([
            'type' => BlockType::Text->value,
            'position' => 0,
            'data' => [
                'heading' => $title,
                'body' => "<p>Contenido de la página <strong>{$title}</strong>.</p>",
            ],
        ]);
    }
}
