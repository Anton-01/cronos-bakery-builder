<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Infrastructure\Database\Seeders;

use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use App\Modules\ProductBuilder\Domain\Enums\PriceModifierType;
use App\Modules\ProductBuilder\Domain\Enums\RuleAction;
use App\Modules\ProductBuilder\Domain\Enums\RuleOperator;
use App\Modules\ProductBuilder\Domain\Models\OptionTemplate;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the configurable cake catalogue, including a fully-configured
 * "Signature Cake" demonstrating dynamic pricing and a conditional rule
 * (Forma = Domo → show Perlas).
 */
class ProductBuilderSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::where('slug', 'signature-cake')->doesntExist()) {
            $this->seedSignatureCake();

            foreach (['Muse Blanc', 'Studio Cake', 'Coquette Cake'] as $position => $name) {
                $this->seedSimpleCake($name, 3000 + $position * 500, $position + 1);
            }
        }

        if (OptionTemplate::count() === 0) {
            $this->seedOptionTemplates();
        }
    }

    private function seedSignatureCake(): void
    {
        $product = Product::factory()->create([
            'name' => 'Signature Cake',
            'slug' => 'signature-cake',
            'description' => 'Nuestro pastel insignia, totalmente personalizable.',
            'base_price_amount' => 4000,
            'position' => 0,
        ]);

        // Forma (radio, required) — drives the base price via "set".
        $forma = $product->options()->create([
            'key' => 'forma', 'label' => 'Forma', 'type' => OptionType::Radio->value,
            'is_required' => true, 'position' => 0,
        ]);
        $forma->values()->createMany([
            ['label' => 'Redonda', 'value' => 'redonda', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 4000, 'is_default' => true, 'position' => 0],
            ['label' => 'Cuadrada', 'value' => 'cuadrada', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 4500, 'position' => 1],
            ['label' => 'Domo', 'value' => 'domo', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 5500, 'position' => 2],
        ]);

        // Color (color swatch).
        $color = $product->options()->create([
            'key' => 'color', 'label' => 'Color', 'type' => OptionType::Color->value, 'position' => 1,
        ]);
        $color->values()->createMany([
            ['label' => 'Blanco', 'value' => 'blanco', 'metadata' => ['hex' => '#ffffff'], 'is_default' => true, 'position' => 0],
            ['label' => 'Rosa', 'value' => 'rosa', 'metadata' => ['hex' => '#f7c5d9'], 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 300, 'position' => 1],
            ['label' => 'Dorado', 'value' => 'dorado', 'metadata' => ['hex' => '#e0a458'], 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 600, 'position' => 2],
        ]);

        // Paleta floral (select).
        $paleta = $product->options()->create([
            'key' => 'paleta-floral', 'label' => 'Paleta floral', 'type' => OptionType::Select->value, 'position' => 2,
        ]);
        $paleta->values()->createMany([
            ['label' => 'Ninguna', 'value' => 'ninguna', 'is_default' => true, 'position' => 0],
            ['label' => 'Primavera', 'value' => 'primavera', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 800, 'position' => 1],
            ['label' => 'Silvestre', 'value' => 'silvestre', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 900, 'position' => 2],
        ]);

        // Decoraciones (checkbox, multiple).
        $deco = $product->options()->create([
            'key' => 'decoraciones', 'label' => 'Decoraciones', 'type' => OptionType::Checkbox->value, 'position' => 3,
        ]);
        $deco->values()->createMany([
            ['label' => 'Frutos rojos', 'value' => 'frutos-rojos', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 500, 'position' => 0],
            ['label' => 'Hojas de oro', 'value' => 'hojas-oro', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 1200, 'position' => 1],
        ]);

        // Perlas (checkbox) — hidden until Forma = Domo.
        $perlas = $product->options()->create([
            'key' => 'perlas', 'label' => 'Perlas', 'type' => OptionType::Checkbox->value, 'position' => 4,
        ]);
        $perlas->values()->createMany([
            ['label' => 'Perlas comestibles', 'value' => 'perlas-comestibles', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 700, 'position' => 0],
        ]);

        // Texto personalizado (textarea).
        $product->options()->create([
            'key' => 'mensaje', 'label' => 'Texto personalizado', 'type' => OptionType::Textarea->value,
            'help_text' => 'Mensaje a escribir en el pastel.', 'position' => 5,
            'config' => ['max_length' => 80],
        ]);

        // Rule: Si Forma = Domo, mostrar Perlas.
        $product->rules()->create([
            'option_id' => $perlas->id,
            'depends_on_option_id' => $forma->id,
            'operator' => RuleOperator::Equals->value,
            'value' => 'domo',
            'action' => RuleAction::Show->value,
            'position' => 0,
        ]);
    }

    private function seedOptionTemplates(): void
    {
        // Forma (radio, required) — drives the base price via "set".
        $forma = OptionTemplate::create([
            'key' => 'forma', 'label' => 'Forma', 'type' => OptionType::Radio->value,
            'is_required' => true, 'position' => 0,
        ]);
        $forma->values()->createMany([
            ['label' => 'Redondo', 'value' => 'redondo', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 4000, 'is_default' => true, 'position' => 0],
            ['label' => 'Cuadrado', 'value' => 'cuadrado', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 4500, 'position' => 1],
            ['label' => 'Domo', 'value' => 'domo', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 5500, 'position' => 2],
        ]);

        // Color (color swatch).
        $color = OptionTemplate::create([
            'key' => 'color', 'label' => 'Color', 'type' => OptionType::Color->value, 'position' => 1,
        ]);
        $color->values()->createMany([
            ['label' => 'Blanco', 'value' => '#FFFFFF', 'position' => 0],
            ['label' => 'Rosa', 'value' => '#F8B4C8', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 300, 'position' => 1],
            ['label' => 'Azul Cielo', 'value' => '#87CEEB', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 600, 'position' => 2],
        ]);

        // Paleta Floral (select).
        $paleta = OptionTemplate::create([
            'key' => 'paleta-floral', 'label' => 'Paleta Floral', 'type' => OptionType::Select->value, 'position' => 2,
        ]);
        $paleta->values()->createMany([
            ['label' => 'Sin flores', 'value' => 'sin-flores', 'is_default' => true, 'position' => 0],
            ['label' => 'Rosas', 'value' => 'rosas', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 800, 'position' => 1],
            ['label' => 'Mixta', 'value' => 'mixta', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 900, 'position' => 2],
        ]);

        // Decoraciones (checkbox, multiple).
        $deco = OptionTemplate::create([
            'key' => 'decoraciones', 'label' => 'Decoraciones', 'type' => OptionType::Checkbox->value, 'position' => 3,
        ]);
        $deco->values()->createMany([
            ['label' => 'Sprinkles', 'value' => 'sprinkles', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 500, 'position' => 0],
            ['label' => 'Macarons', 'value' => 'macarons', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 1200, 'position' => 1],
        ]);

        // Perlas (checkbox).
        $perlas = OptionTemplate::create([
            'key' => 'perlas', 'label' => 'Perlas', 'type' => OptionType::Checkbox->value, 'position' => 4,
        ]);
        $perlas->values()->createMany([
            ['label' => 'Con perlas', 'value' => 'con-perlas', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 700, 'position' => 0],
        ]);

        // Tamaño (select).
        $tamano = OptionTemplate::create([
            'key' => 'tamano', 'label' => 'Tamaño', 'type' => OptionType::Select->value, 'position' => 5,
        ]);
        $tamano->values()->createMany([
            ['label' => 'Chico', 'value' => 'chico', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 3500, 'is_default' => true, 'position' => 0],
            ['label' => 'Mediano', 'value' => 'mediano', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 4500, 'position' => 1],
            ['label' => 'Grande', 'value' => 'grande', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 6000, 'position' => 2],
        ]);
    }

    private function seedSimpleCake(string $name, int $basePrice, int $position): void
    {
        $product = Product::factory()->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'base_price_amount' => $basePrice,
            'position' => $position,
        ]);

        $size = $product->options()->create([
            'key' => 'tamano', 'label' => 'Tamaño', 'type' => OptionType::Select->value,
            'is_required' => true, 'position' => 0,
        ]);
        $size->values()->createMany([
            ['label' => 'Pequeño', 'value' => 'pequeno', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => $basePrice, 'is_default' => true, 'position' => 0],
            ['label' => 'Mediano', 'value' => 'mediano', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => $basePrice + 1500, 'position' => 1],
            ['label' => 'Grande', 'value' => 'grande', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => $basePrice + 3000, 'position' => 2],
        ]);
    }
}
