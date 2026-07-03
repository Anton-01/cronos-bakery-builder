<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('catalog_category_product', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('catalog_categories')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['product_id', 'category_id']);
        });

        Schema::create('catalog_collection_product', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->foreignId('collection_id')->constrained('catalog_collections')->cascadeOnDelete();
            $table->primary(['product_id', 'collection_id']);
        });

        Schema::create('catalog_attribute_value_product', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('catalog_attribute_values')->cascadeOnDelete();
            $table->primary(['product_id', 'attribute_value_id'], 'cat_attr_val_prod_primary');
        });

        Schema::create('catalog_product_tag', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('catalog_tags')->cascadeOnDelete();
            $table->primary(['product_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_tag');
        Schema::dropIfExists('catalog_attribute_value_product');
        Schema::dropIfExists('catalog_collection_product');
        Schema::dropIfExists('catalog_category_product');
    }
};
