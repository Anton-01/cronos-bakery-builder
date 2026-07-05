<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Admin-managed gateway instances, isolated per brand (tenant).
        // PK: native PostgreSQL identity (regla §13 — no UUIDs as keys).
        Schema::create('payment_gateways', function (Blueprint $table): void {
            $table->id();
            // null = shared/default gateway available to every brand.
            $table->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $table->string('driver_name'); // key into config('payments.drivers')
            $table->string('name'); // admin-facing display name
            $table->string('environment')->default('sandbox'); // sandbox | production
            $table->boolean('is_active')->default(false);
            // Plaintext keys, per-value encrypted secrets (EncryptedCredentials cast).
            $table->jsonb('credentials')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'is_active']);
            // Uniqueness of (brand, driver) among live rows is enforced at the
            // FormRequest level because soft-deleted rows must not block re-creation.
            $table->index(['brand_id', 'driver_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
