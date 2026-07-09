<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Weekly opening schedule + per-day capacity (0 = unlimited).
        Schema::create('calendar_schedule_days', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('weekday')->unique(); // 0=Sunday .. 6=Saturday
            $table->boolean('is_open')->default(true);
            $table->unsignedInteger('capacity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_schedule_days');
    }
};
