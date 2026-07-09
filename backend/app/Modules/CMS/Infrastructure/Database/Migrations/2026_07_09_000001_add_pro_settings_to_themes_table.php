<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Theme Builder PRO: configuraciones dinámicas en columnas JSONB dedicadas.
 * Cada dominio de personalización evoluciona sin alterar el esquema
 * relacional — agregar una opción nueva es agregar una clave al documento.
 *
 *  - color_palette:       primary/secondary/accent/background/surface/text (hex)
 *  - typography_settings: fuentes de títulos y cuerpo, pesos, tamaño base
 *  - layout_config:       header sticky, footer expandido, ancho contenedor…
 *  - custom_scripts:      snippets head/body_start/body_end (GA, Pixels)
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table): void {
            $table->jsonb('color_palette')->nullable()->after('colors');
            $table->jsonb('typography_settings')->nullable()->after('fonts');
            $table->jsonb('layout_config')->nullable()->after('typography_settings');
            $table->jsonb('custom_scripts')->nullable()->after('layout_config');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table): void {
            $table->dropColumn(['color_palette', 'typography_settings', 'layout_config', 'custom_scripts']);
        });
    }
};
