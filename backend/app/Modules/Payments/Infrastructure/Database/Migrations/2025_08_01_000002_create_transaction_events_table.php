<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Full traceability: every initiation, webhook, retry and status change.
        Schema::create('transaction_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('type'); // created | webhook | retry | status_change | reconciled | refund
            $table->string('status')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_events');
    }
};
