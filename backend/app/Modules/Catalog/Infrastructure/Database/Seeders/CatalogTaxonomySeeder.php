<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Domain\Models\Attribute;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Collection;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds catalog categories, a collection, filterable attributes and a handful
 * of products wired to them so the storefront filters work out of the box.
 */
class CatalogTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $collection = Collection::factory()->create(['name' => 'Temporada', 'slug' => 'temporada']);
        $attributes = $this->seedAttributes();

        $this->seedProducts($categories, $collection, $attributes);
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $categories = [];
        foreach (['Floral', 'Moderno', 'Mini', 'Signature'] as $position => $name) {
            $categories[$name] = Category::factory()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'position' => $position,
            ]);
        }

        return $categories;
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, \App\Modules\Catalog\Domain\Models\AttributeValue>>
     */
    private function seedAttributes(): array
    {
        $tamano = Attribute::factory()->create(['name' => 'Tamaño', 'code' => 'tamano', 'position' => 0]);
        $sabor = Attribute::factory()->create(['name' => 'Sabor', 'code' => 'sabor', 'position' => 1]);
        $color = Attribute::factory()->color()->create(['name' => 'Color', 'code' => 'color', 'position' => 2]);

        $tamanoValues = collect(['Pequeño' => 'pequeno', 'Mediano' => 'mediano', 'Grande' => 'grande'])
            ->map(fn ($value, $label) => $tamano->values()->create(['label' => $label, 'value' => $value]))
            ->values();

        $saborValues = collect(['Chocolate' => 'chocolate', 'Vainilla' => 'vainilla', 'Red Velvet' => 'red-velvet'])
            ->map(fn ($value, $label) => $sabor->values()->create(['label' => $label, 'value' => $value]))
            ->values();

        $colorValues = collect([
            ['Blanco', 'blanco', '#ffffff'],
            ['Rosa', 'rosa', '#f7c5d9'],
            ['Dorado', 'dorado', '#e0a458'],
        ])->map(fn ($c) => $color->values()->create([
            'label' => $c[0], 'value' => $c[1], 'metadata' => ['hex' => $c[2]],
        ]));

        return ['tamano' => $tamanoValues, 'sabor' => $saborValues, 'color' => $colorValues];
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, \Illuminate\Support\Collection<int, \App\Modules\Catalog\Domain\Models\AttributeValue>>  $attributes
     */
    private function seedProducts(array $categories, Collection $collection, array $attributes): void
    {
        $catalogue = [
            ['Muse Blanc', 'Floral', 4500],
            ['Studio Cake', 'Moderno', 5200],
            ['Coquette Cake', 'Mini', 2800],
            ['Signature Cake', 'Signature', 6500],
            ['Petite Rose', 'Floral', 3100],
            ['Mono Noir', 'Moderno', 5800],
        ];

        foreach ($catalogue as $position => [$name, $categoryName, $price]) {
            $product = Product::factory()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'price_amount' => $price,
                'meta_title' => "{$name} — Cronos Bakery",
                'meta_description' => "Ordena {$name}, un pastel artesanal de la categoría {$categoryName}.",
                'position' => $position,
            ]);

            $product->categories()->attach($categories[$categoryName]->id, ['is_primary' => true]);
            $product->collections()->attach($collection->id);
            $product->attributeValues()->attach([
                $attributes['tamano']->random()->id,
                $attributes['sabor']->random()->id,
                $attributes['color']->random()->id,
            ]);
        }
    }
}
