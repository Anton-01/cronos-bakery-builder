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

        // Evitamos duplicados en Collection usando updateOrCreate
        $collectionFactory = Collection::factory()->raw();
        $collection = Collection::updateOrCreate(
            ['slug' => 'temporada'],
            array_merge($collectionFactory, ['name' => 'Temporada'])
        );

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
            $slug = Str::slug($name);

            // 💡 IMPORTANTE: El factory debe ser "raw" por cada vuelta del bucle
            $factoryAttributes = Category::factory()->raw();

            $categories[$name] = Category::updateOrCreate(
                ['slug' => $slug],
                array_merge($factoryAttributes, [
                    'name' => $name,
                    'slug' => $slug, // Forzamos a que use nuestro slug limpio
                    'position' => $position,
                ])
            );
        }

        return $categories;
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, \App\Modules\Catalog\Domain\Models\AttributeValue>>
     */
    /**
     * @return array<string, \Illuminate\Support\Collection<int, \App\Modules\Catalog\Domain\Models\AttributeValue>>
     */
    private function seedAttributes(): array
    {
        // Forzamos explícitamente el 'code' correcto dentro del array_merge
        $tamano = Attribute::updateOrCreate(
            ['code' => 'tamano'],
            array_merge(Attribute::factory()->raw(), ['name' => 'Tamaño', 'code' => 'tamano', 'position' => 0])
        );

        $sabor = Attribute::updateOrCreate(
            ['code' => 'sabor'],
            array_merge(Attribute::factory()->raw(), ['name' => 'Sabor', 'code' => 'sabor', 'position' => 1])
        );

        $color = Attribute::updateOrCreate(
            ['code' => 'color'],
            array_merge(Attribute::factory()->color()->raw(), ['name' => 'Color', 'code' => 'color', 'position' => 2])
        );

        // Limpiamos los valores viejos para no acumular duplicados en las tablas hijas
        $tamano->values()->delete();
        $sabor->values()->delete();
        $color->values()->delete();

        // Re-creación de los valores (este bloque se queda igual)
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
            $slug = Str::slug($name);

            // Generamos atributos falsos frescos por cada vuelta
            $productFactory = Product::factory()->raw();

            $product = Product::updateOrCreate(
                ['slug' => $slug], // 1. Condición de búsqueda
                array_merge($productFactory, [ // 2. Datos a insertar/actualizar
                    'name' => $name,
                    'slug' => $slug, // 💡 OBLIGATORIO: Forzamos nuestro slug limpio aquí también
                    'price_amount' => $price,
                    'meta_title' => "{$name} — Cronos Bakery",
                    'meta_description' => "Ordena {$name}, un pastel artesanal de la categoría {$categoryName}.",
                    'position' => $position,
                ])
            );

            // Sincronización de relaciones muchos a muchos (se mantiene igual)
            $product->categories()->sync([$categories[$categoryName]->id => ['is_primary' => true]]);
            $product->collections()->sync([$collection->id]);

            $product->attributeValues()->sync([
                $attributes['tamano']->random()->id,
                $attributes['sabor']->random()->id,
                $attributes['color']->random()->id,
            ]);
        }
    }
}
