<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pb_option_template_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('pb_option_templates')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->string('price_modifier_type')->default('none');
            $table->bigInteger('price_modifier_amount')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['template_id', 'value']);
            $table->index(['template_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_option_template_values');
    }
};
