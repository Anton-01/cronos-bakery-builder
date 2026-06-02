<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Automatic reminders fired N hours before the fulfillment time
        // (e.g. 24h / 12h / 2h before pickup).
        Schema::create('reminder_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedInteger('offset_hours');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique('offset_hours');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_rules');
    }
};
