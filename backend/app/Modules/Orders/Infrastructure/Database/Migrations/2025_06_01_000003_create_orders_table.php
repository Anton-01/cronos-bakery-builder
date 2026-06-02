<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending')->index();

            $table->string('fulfillment_type'); // delivery | pickup

            // Delivery: snapshot of the chosen address (immutable on the order).
            $table->json('shipping_address')->nullable();

            // Pickup: branch + scheduled slot.
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('pickup_date')->nullable();
            $table->string('pickup_time')->nullable();

            $table->unsignedBigInteger('subtotal_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->uuid('product_id');
            $table->string('product_name');
            $table->string('product_slug');
            $table->json('configuration');
            $table->unsignedBigInteger('unit_price_amount');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total_amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
