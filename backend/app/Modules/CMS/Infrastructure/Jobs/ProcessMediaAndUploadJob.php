<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Jobs;

use App\Modules\CMS\Domain\Models\MediaAsset;
use App\Modules\CMS\Domain\Models\StorageProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class ProcessMediaAndUploadJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct(
        public readonly string $mediaAssetId,
        public readonly array $transformOptions = [],
    ) {
        $this->onQueue('media-processing');
    }

    public function handle(ImageManager $imageManager): void
    {
        $asset = MediaAsset::query()->findOrFail($this->mediaAssetId);

        if (! $asset->isPending()) {
            return;
        }

        $asset->markProcessing();

        $localDisk = Storage::disk('local');
        $sourcePath = $asset->path;

        if (! $localDisk->exists($sourcePath)) {
            $asset->markFailed();
            Log::error('ProcessMedia: source file missing', ['asset_id' => $asset->id, 'path' => $sourcePath]);
            return;
        }

        $transformations = [];
        $processedPath = $sourcePath;

        if ($this->isImage($asset->mime_type)) {
            $processedPath = $this->processImage($imageManager, $localDisk, $sourcePath, $transformations);
        }

        $provider = $this->resolveStorageProvider($asset);
        $remoteDiskName = $this->registerRemoteDisk($provider);
        $remoteDisk = Storage::disk($remoteDiskName);

        $remotePath = $this->buildRemotePath($asset);
        $remoteDisk->put($remotePath, $localDisk->get($processedPath));

        $asset->markCompleted($remoteDiskName, $remotePath, $transformations ?: null);

        $localDisk->delete($sourcePath);
        if ($processedPath !== $sourcePath) {
            $localDisk->delete($processedPath);
        }
    }

    public function failed(\Throwable $exception): void
    {
        MediaAsset::query()
            ->where('id', $this->mediaAssetId)
            ->update(['processing_status' => 'failed']);

        Log::error('ProcessMedia: job failed', [
            'asset_id' => $this->mediaAssetId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function isImage(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    private function processImage(ImageManager $imageManager, $disk, string $sourcePath, array &$transformations): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'media_');
        file_put_contents($tempFile, $disk->get($sourcePath));

        $image = $imageManager->read($tempFile);

        $maxWidth = $this->transformOptions['max_width'] ?? 2048;
        $maxHeight = $this->transformOptions['max_height'] ?? 2048;

        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->scaleDown($maxWidth, $maxHeight);
            $transformations['resized'] = ['width' => $image->width(), 'height' => $image->height()];
        }

        $webpPath = pathinfo($sourcePath, PATHINFO_DIRNAME)
            . '/' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.webp';

        $quality = $this->transformOptions['quality'] ?? 80;
        $encoded = $image->toWebp($quality);
        $disk->put($webpPath, (string) $encoded);

        $transformations['format'] = 'webp';
        $transformations['quality'] = $quality;

        @unlink($tempFile);

        return $webpPath;
    }

    private function resolveStorageProvider(MediaAsset $asset): StorageProvider
    {
        if ($asset->storage_provider_id !== null) {
            return StorageProvider::query()->findOrFail($asset->storage_provider_id);
        }

        return StorageProvider::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function registerRemoteDisk(StorageProvider $provider): string
    {
        $diskName = "dynamic_{$provider->id}";

        Config::set("filesystems.disks.{$diskName}", $provider->toDiskConfig());

        return $diskName;
    }

    private function buildRemotePath(MediaAsset $asset): string
    {
        $date = now()->format('Y/m');
        $filename = Str::uuid() . '_' . Str::slug(pathinfo($asset->original_name, PATHINFO_FILENAME)) . '.webp';

        return "media/{$date}/{$filename}";
    }
}
