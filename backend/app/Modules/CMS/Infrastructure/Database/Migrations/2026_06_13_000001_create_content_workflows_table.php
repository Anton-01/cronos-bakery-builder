<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('content_workflows', function (Blueprint $table): void {
            $table->id();
            $table->morphs('workflowable');
            $table->string('from_status');
            $table->string('to_status');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->index(['workflowable_type', 'workflowable_id'], 'cw_poly_idx');
        });

        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->foreignId('last_editor_id')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->after('last_editor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_workflows');

        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('last_editor_id');
            $table->dropColumn('scheduled_at');
        });
    }
};
