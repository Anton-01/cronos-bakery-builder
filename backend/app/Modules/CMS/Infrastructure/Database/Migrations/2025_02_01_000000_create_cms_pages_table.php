<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('type')->default('landing')->index();

            // SEO metadata.
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            // Optional rich-text body (e.g. blog / policies pages).
            $table->longText('content')->nullable();

            // Page-level presentation settings (layout, container width, theme
            // overrides…). Free-form JSONB interpreted by the frontend.
            $table->jsonb('settings')->nullable();

            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // A slug identifies a page within a brand, not globally.
            $table->unique(['brand_id', 'slug']);
            $table->index(['brand_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};
