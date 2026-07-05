<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->string('avatar')->nullable()->after('email'); // storage path (or absolute URL)
            $table->string('phone', 30)->nullable()->after('avatar');
            $table->jsonb('notification_settings')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropColumn(['avatar', 'phone', 'notification_settings']);
        });
    }
};
