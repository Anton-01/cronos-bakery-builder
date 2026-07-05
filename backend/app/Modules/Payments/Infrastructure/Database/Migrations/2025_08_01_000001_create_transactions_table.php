<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            // Orders module is still UUID-keyed (§13 pending); this is a
            // non-key reference column, so it does not violate the identity rule.
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->restrictOnDelete();
            $table->string('provider_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('amount'); // minor units (cents)
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending')->index();
            $table->jsonb('raw_response')->nullable(); // last provider payload, for audit
            $table->jsonb('checkout')->nullable(); // actionable payload for the frontend
            $table->string('idempotency_key');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_gateway_id', 'idempotency_key']);
            $table->index(['brand_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
