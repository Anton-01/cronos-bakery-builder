<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAsset extends Model
{
    protected $fillable = [
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size',
        'transformations',
        'processing_status',
        'storage_provider_id',
        'uploaded_by',
    ];

    protected $casts = [
        'transformations' => 'array',
        'size' => 'integer',
    ];

    /** @return BelongsTo<StorageProvider, $this> */
    public function storageProvider(): BelongsTo
    {
        return $this->belongsTo(StorageProvider::class);
    }

    public function isPending(): bool
    {
        return $this->processing_status === 'pending';
    }

    public function markProcessing(): void
    {
        $this->update(['processing_status' => 'processing']);
    }

    public function markCompleted(string $disk, string $path, ?array $transformations = null): void
    {
        $this->update([
            'processing_status' => 'completed',
            'disk' => $disk,
            'path' => $path,
            'transformations' => $transformations,
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['processing_status' => 'failed']);
    }
}
