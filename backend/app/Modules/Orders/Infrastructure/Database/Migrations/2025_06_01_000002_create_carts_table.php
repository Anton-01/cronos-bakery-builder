<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // One persistent cart per customer.
        Schema::create('carts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained('carts')->cascadeOnDelete();
            // Snapshot of the configured Product Builder product.
            $table->uuid('product_id');
            $table->string('product_name');
            $table->string('product_slug');
            // Full cake configuration: selections + priced breakdown.
            $table->json('configuration');
            $table->unsignedBigInteger('unit_price_amount');
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->index('cart_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
