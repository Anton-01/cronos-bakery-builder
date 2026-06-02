<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Full traceability: every initiation, webhook, retry and status change.
        Schema::create('payment_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('type'); // created | webhook | retry | status_change | reconciled
            $table->string('status')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
