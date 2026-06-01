<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('meta_title')->nullable()->after('description');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('image')->nullable()->after('meta_description');
            $table->unsignedInteger('position')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn(['meta_title', 'meta_description', 'image', 'position']);
        });
    }
};
