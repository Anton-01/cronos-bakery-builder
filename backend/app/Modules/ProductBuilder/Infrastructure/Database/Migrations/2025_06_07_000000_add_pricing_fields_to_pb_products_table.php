<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pb_products', function (Blueprint $table): void {
            $table->string('discount_type', 20)->default('none')->after('currency');
            $table->unsignedBigInteger('discount_value')->default(0)->after('discount_type');
            $table->string('tax_class', 30)->default('standard')->after('discount_value');
            $table->unsignedInteger('vat')->default(16)->after('tax_class');
            $table->text('tags')->nullable()->after('vat');
        });
    }

    public function down(): void
    {
        Schema::table('pb_products', function (Blueprint $table): void {
            $table->dropColumn(['discount_type', 'discount_value', 'tax_class', 'vat', 'tags']);
        });
    }
};
