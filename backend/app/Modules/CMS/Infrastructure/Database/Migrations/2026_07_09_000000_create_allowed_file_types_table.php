<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo central de tipos de archivo permitidos para la Media Library.
 * La validación de subida de medios se resuelve dinámicamente contra las
 * filas activas de esta tabla (mime + extensión), en lugar de allow-lists
 * hardcodeadas en FormRequests.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('allowed_file_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Grupo visual para la tabla del admin (Imágenes, Documentos, …).
            $table->string('category')->index();
            $table->text('description')->nullable();
            // Lista de MIME types aceptados (sniffing real del contenido).
            $table->jsonb('mime_types');
            // Extensiones aceptadas, sin punto (jpg, pdf, …).
            $table->jsonb('extensions');
            // Clase de icono PrimeIcons para pintar la UI (ej. "pi pi-image").
            $table->string('icon_reference')->default('pi pi-file');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['category', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowed_file_types');
    }
};
