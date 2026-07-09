<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Per-product production lead time. A null product_id is the global
        // default applied when a product has no specific rule.
        Schema::create('calendar_production_rules', function (Blueprint $table): void {
            $table->id();
            // FK real hacia pb_products (identity, §13). null = regla global.
            $table->foreignId('product_id')->nullable()->unique()
                ->constrained('pb_products')->cascadeOnDelete();
            $table->unsignedInteger('lead_time_hours')->default(48);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_production_rules');
    }
};
