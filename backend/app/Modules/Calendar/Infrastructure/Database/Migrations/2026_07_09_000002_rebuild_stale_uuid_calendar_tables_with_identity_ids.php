<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de REPARACIÓN (idempotente) para bases de desarrollo obsoletas.
 *
 * Las migraciones create_calendar_* se editaron EN SITIO al convertir el
 * módulo a IDs identity (§13/§27), por lo que un `migrate` sin `fresh` deja
 * las tablas de la era UUID: `id uuid NOT NULL` SIN default (HasUuids
 * generaba el ID en la app). Con el trait eliminado, el primer INSERT del
 * seeder revienta con SQLSTATE[23502] "null value in column id".
 *
 * Esta migración detecta ese estado (id no numérico) y reconstruye las 6
 * tablas del módulo con identity nativo. Solo contienen configuración
 * sembrada — reconstruirlas en desarrollo es seguro. En una base creada
 * desde cero (o ya reparada) es un no-op.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! $this->calendarTablesAreStale()) {
            return;
        }

        // Orden inverso de dependencias (bookings/blackouts → time_slots).
        Schema::dropIfExists('calendar_bookings');
        Schema::dropIfExists('calendar_blackouts');
        Schema::dropIfExists('calendar_production_rules');
        Schema::dropIfExists('calendar_holidays');
        Schema::dropIfExists('calendar_time_slots');
        Schema::dropIfExists('calendar_schedule_days');

        // Redefinición idéntica a las migraciones create_calendar_* vigentes.
        Schema::create('calendar_schedule_days', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('weekday')->unique(); // 0=Sunday .. 6=Saturday
            $table->boolean('is_open')->default(true);
            $table->unsignedInteger('capacity')->default(0);
            $table->timestamps();
        });

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

        Schema::create('calendar_holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('name');
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->index('date');
        });

        Schema::create('calendar_blackouts', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->foreignId('time_slot_id')->nullable()
                ->constrained('calendar_time_slots')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['date', 'time_slot_id']);
        });

        Schema::create('calendar_production_rules', function (Blueprint $table): void {
            $table->id();
            // FK real hacia pb_products (identity, §13). null = regla global.
            $table->foreignId('product_id')->nullable()->unique()
                ->constrained('pb_products')->cascadeOnDelete();
            $table->unsignedInteger('lead_time_hours')->default(48);
            $table->timestamps();
        });

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
        // Reparación one-shot: no hay estado anterior válido al que volver.
    }

    /**
     * Una base sana tiene `id` bigint/integer con identity; la era UUID lo
     * tenía como `uuid`. Basta inspeccionar una tabla del módulo.
     */
    private function calendarTablesAreStale(): bool
    {
        if (! Schema::hasTable('calendar_time_slots')) {
            return false; // instalación desde cero: las create_* ya la harán identity
        }

        $type = strtolower(Schema::getColumnType('calendar_time_slots', 'id'));

        return ! in_array($type, ['bigint', 'integer', 'int8', 'int4'], true);
    }
};
