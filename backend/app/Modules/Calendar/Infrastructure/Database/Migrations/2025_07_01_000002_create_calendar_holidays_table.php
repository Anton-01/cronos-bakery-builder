<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Festivos: full-day closures. Recurring holidays match month/day yearly.
        Schema::create('calendar_holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('name');
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_holidays');
    }
};
