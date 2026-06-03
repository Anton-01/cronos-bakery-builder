<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pb_option_values', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('option_id')->constrained('pb_options')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->string('price_modifier_type')->default('none');
            $table->bigInteger('price_modifier_amount')->default(0);
            // Presentation metadata: color hex, image URL, etc.
            $table->json('metadata')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['option_id', 'value']);
            $table->index(['option_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_option_values');
    }
};
