<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Ordered blocks composing a page. A block is either inline (type + data)
        // or a reference to a reusable section (section_id), optionally overriding
        // its data.
        Schema::create('cms_page_sections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('page_id')->constrained('cms_pages')->cascadeOnDelete();
            $table->foreignUuid('section_id')->nullable()->constrained('cms_sections')->nullOnDelete();
            $table->string('type');
            $table->json('data')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['page_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_page_sections');
    }
};
