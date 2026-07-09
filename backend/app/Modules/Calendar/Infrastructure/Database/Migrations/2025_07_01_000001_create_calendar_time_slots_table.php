<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Bookable delivery/pickup windows, each with its own capacity.
        Schema::create('calendar_time_slots', function (Blueprint $table): void {
            $table->id();
            $table->string('label');
            $table->string('start_time'); // HH:MM (24h)
            $table->string('end_time')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_time_slots');
    }
};
