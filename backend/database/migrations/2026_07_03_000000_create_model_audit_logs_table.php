<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Model-level audit trail (who changed what, attribute by attribute).
        // Complements the HTTP-request log in `audit_logs` (Administration):
        // that table records requests; this one records state diffs per model.
        Schema::create('model_audit_logs', function (Blueprint $table): void {
            $table->id();

            // Tenant scope: filled when the audited model is brand-aware.
            $table->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();

            // The admin who performed the change; null for system actions
            // (e.g. scheduled jobs, seeders).
            $table->foreignId('user_id')->nullable()->constrained('admins')->nullOnDelete();

            $table->string('event'); // created | updated | deleted | restored
            $table->morphs('auditable');

            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['brand_id', 'created_at']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_audit_logs');
    }
};
