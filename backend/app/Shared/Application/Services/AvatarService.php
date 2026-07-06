<?php

declare(strict_types=1);

namespace App\Shared\Application\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Avatar storage policy (MinIO / S3-compatible):
 *
 * - Disk from AVATAR_DISK (default "public"; "s3" targets MinIO via the
 *   AWS_* env vars). The DB stores the storage PATH, never a hardcoded URL,
 *   so the bucket/endpoint can change without data migrations.
 * - Filenames are random UUIDs (allowed for non-key values per §13) under
 *   avatars/{Y}/{m}/ — the original client filename never reaches storage.
 * - MIME/size validation happens in the FormRequest; this service only
 *   persists, swaps atomically (upload new → delete old) and resolves URLs.
 */
final class AvatarService
{
    public function disk(): string
    {
        return (string) config('filesystems.avatar_disk', 'public');
    }

    /**
     * Store the new avatar and remove the previous one. Returns the new path.
     */
    public function replace(UploadedFile $file, ?string $previousPath): string
    {
        $path = $file->storePubliclyAs(
            'avatars/' . now()->format('Y/m'),
            Str::uuid() . '.' . strtolower($file->getClientOriginalExtension()),
            $this->disk(),
        );

        $this->delete($previousPath);

        return (string) $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '' && ! str_starts_with($path, 'http')) {
            Storage::disk($this->disk())->delete($path);
        }
    }

    /**
     * Public URL for a stored avatar. Absolute URLs (e.g. social login
     * avatars) pass through untouched.
     */
    public function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::disk($this->disk())->url($path);
    }

    /**
     * Convenience for models exposing an `avatar` attribute.
     */
    public function urlFor(Model $model): ?string
    {
        return $this->url($model->getAttribute('avatar'));
    }
}
