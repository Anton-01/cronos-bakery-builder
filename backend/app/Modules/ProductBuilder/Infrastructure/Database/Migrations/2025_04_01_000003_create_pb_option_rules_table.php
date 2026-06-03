<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Conditional dependency rules, e.g. "if Forma = Domo, show Perlas".
        Schema::create('pb_option_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('pb_products')->cascadeOnDelete();
            // The option whose visibility is affected.
            $table->foreignUuid('option_id')->constrained('pb_options')->cascadeOnDelete();
            // The source option whose selected value triggers the rule.
            $table->foreignUuid('depends_on_option_id')->constrained('pb_options')->cascadeOnDelete();
            $table->string('operator')->default('equals');
            $table->string('value');
            $table->string('action')->default('show');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_option_rules');
    }
};
