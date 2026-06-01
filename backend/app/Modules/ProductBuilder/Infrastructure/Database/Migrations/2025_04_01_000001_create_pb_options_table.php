<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pb_options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('pb_products')->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('type');
            $table->string('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('position')->default(0);
            // Type-specific config (e.g. min/max selections, max text length).
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'key']);
            $table->index(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_options');
    }
};
