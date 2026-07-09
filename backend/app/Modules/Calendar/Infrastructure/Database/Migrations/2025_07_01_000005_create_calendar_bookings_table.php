<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Reservations consumed against day/slot capacity by placed orders.
        Schema::create('calendar_bookings', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->foreignId('time_slot_id')->nullable()
                ->constrained('calendar_time_slots')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('reference')->nullable(); // e.g. order number
            $table->timestamps();

            $table->index(['date', 'time_slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_bookings');
    }
};
