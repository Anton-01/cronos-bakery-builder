<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pb_product_option_links', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('pb_products')->cascadeOnDelete();
            $table->foreignUuid('template_id')->constrained('pb_option_templates')->cascadeOnDelete();
            $table->text('legend')->nullable(); // Rich text HTML
            $table->json('enabled_value_ids')->nullable(); // null = all values, array = specific IDs
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'template_id']);
            $table->index(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_product_option_links');
    }
};
