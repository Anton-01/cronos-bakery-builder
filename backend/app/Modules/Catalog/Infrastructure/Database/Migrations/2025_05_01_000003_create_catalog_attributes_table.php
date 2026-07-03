<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Admin-defined product attributes (e.g. Tamaño, Sabor, Color). Flagging
        // an attribute as filterable surfaces it as a catalog filter — no code.
        Schema::create('catalog_attributes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('select'); // select | color
            $table->boolean('is_filterable')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('catalog_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_id')->constrained('catalog_attributes')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->jsonb('metadata')->nullable(); // e.g. color hex
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_attribute_values');
        Schema::dropIfExists('catalog_attributes');
    }
};
