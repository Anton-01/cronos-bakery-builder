<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('content_versions', function (Blueprint $table): void {
            $table->id();
            $table->morphs('versionable');
            $table->unsignedInteger('version_number');
            $table->json('payload_before')->nullable();
            $table->json('payload_after');
            $table->string('status_before')->nullable();
            $table->string('status_after');
            $table->string('change_summary', 500)->nullable();
            $table->foreignId('author_id')->constrained('admins')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['versionable_type', 'versionable_id', 'version_number'], 'cv_poly_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_versions');
    }
};
