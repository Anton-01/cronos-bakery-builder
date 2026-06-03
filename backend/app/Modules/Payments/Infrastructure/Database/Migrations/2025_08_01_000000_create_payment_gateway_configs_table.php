<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Admin-managed gateway configuration: per provider mode + credentials.
        Schema::create('payment_gateway_configs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('gateway')->unique(); // mercadopago | stripe | openpay
            $table->string('mode')->default('sandbox'); // sandbox | production
            $table->text('credentials')->nullable(); // encrypted JSON per mode
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_configs');
    }
};
