<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Advanced session tracking: every Sanctum token knows the client that
        // created it, so users/admins can review and revoke specific devices.
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('abilities');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('device_name', 120)->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropColumn(['ip_address', 'user_agent', 'device_name']);
        });
    }
};
