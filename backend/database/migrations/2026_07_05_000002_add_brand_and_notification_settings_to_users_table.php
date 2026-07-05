<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Multi-tenant isolation: a customer belongs to the brand they signed
        // up under (null = legacy/global account). PKs remain identity (§13).
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('brand_id')->nullable()->after('id')
                ->constrained('brands')->nullOnDelete();
            $table->jsonb('notification_settings')->nullable()->after('avatar');

            $table->index(['brand_id', 'is_suspended']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn('notification_settings');
        });
    }
};
