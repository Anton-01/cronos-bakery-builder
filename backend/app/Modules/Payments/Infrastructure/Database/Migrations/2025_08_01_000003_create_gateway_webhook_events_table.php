<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Idempotency ledger for inbound webhooks: the unique constraint on
        // (gateway, provider event id) makes duplicate deliveries a no-op at
        // the database level, even under concurrent requests.
        Schema::create('gateway_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->string('provider_event_id');
            $table->string('event_type')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_gateway_id', 'provider_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_webhook_events');
    }
};
