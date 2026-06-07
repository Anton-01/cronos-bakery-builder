<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // 1. Creamos la estructura base de la tabla
        Schema::create('catalog_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            // Definimos la columna parent_id como UUID aceptando nulos, pero SIN el .constrained()
            $table->uuid('parent_id')->nullable();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // 2. Ahora que la tabla existe formalmente, añadimos la clave foránea reflexiva
        Schema::table('catalog_categories', function (Blueprint $table): void {
            $table->foreign('parent_id')
                ->references('id')
                ->on('catalog_categories')
                ->nullOnDelete(); // Mantenemos el nullOnDelete que tenías original
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_categories');
    }
};
