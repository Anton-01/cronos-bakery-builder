<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');

            // Branding assets.
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();

            // Corporate palette: primary, secondary, accent, success, warning, danger.
            $table->jsonb('colors');

            // Google Fonts: heading & body families (+ optional stylesheet URL).
            $table->jsonb('fonts');

            // Visual footer editor payload (columns, links, copyright).
            $table->jsonb('footer')->nullable();

            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
