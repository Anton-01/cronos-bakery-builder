<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Audit trail: every mutating admin action is recorded automatically.
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('admin_name')->nullable();
            $table->string('method');
            $table->string('path');
            $table->string('route_name')->nullable();
            $table->unsignedSmallInteger('status_code');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['admin_id', 'created_at']);
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
