<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Ordered builder blocks composing a page (WordPress-style). A block is
        // either inline (type + data) or a reference to a reusable section
        // (section_id), optionally overriding its data. The type-specific
        // payload lives in a JSONB column interpreted by the frontend renderer.
        Schema::create('cms_page_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('cms_pages')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('cms_sections')->nullOnDelete();
            $table->string('type');
            $table->jsonb('data')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['page_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_page_blocks');
    }
};
