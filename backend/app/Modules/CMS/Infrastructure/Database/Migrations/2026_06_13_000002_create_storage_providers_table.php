<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('storage_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('driver')->index(); // s3, gcs, azure
            $table->jsonb('credentials');       // encrypted at model level
            $table->string('bucket');
            $table->string('region')->nullable();
            $table->string('endpoint')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('original_name');
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->jsonb('transformations')->nullable();
            $table->string('processing_status')->default('pending')->index(); // pending, processing, completed, failed
            $table->unsignedBigInteger('storage_provider_id')->nullable();
            // Los medios los sube el panel: el actor es un Admin (guard admin).
            $table->foreignId('uploaded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->foreign('storage_provider_id')->references('id')->on('storage_providers')->nullOnDelete();
        });

        Schema::create('cache_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('tag')->unique();
            $table->unsignedInteger('ttl_seconds')->default(3600);
            $table->timestamp('last_flushed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_settings');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('storage_providers');
    }
};
