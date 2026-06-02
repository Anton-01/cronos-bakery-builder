<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Bloqueos: ad-hoc blocks. A null time_slot_id blocks the whole day,
        // otherwise only the referenced slot on that date.
        Schema::create('calendar_blackouts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->foreignUuid('time_slot_id')->nullable()
                ->constrained('calendar_time_slots')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['date', 'time_slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_blackouts');
    }
};
