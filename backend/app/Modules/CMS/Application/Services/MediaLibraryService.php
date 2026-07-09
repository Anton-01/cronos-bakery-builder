<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Services;

use App\Modules\CMS\Domain\Models\AllowedFileType;
use App\Modules\CMS\Domain\Models\MediaAsset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Media Library centralizada. La validación de tipo NO usa allow-lists
 * hardcodeadas: consulta las filas activas de `allowed_file_types` (mime
 * real detectado por sniffing + extensión declarada) — el administrador
 * gobierna qué se puede subir prendiendo/apagando tipos desde el panel.
 */
final class MediaLibraryService
{
    private const MAX_SIZE_KB = 20480; // 20 MB

    /**
     * @param  array{search?: string|null, mime?: string|null, file_type_id?: int|null, per_page?: int|null}  $filters
     * @return LengthAwarePaginator<int, MediaAsset>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return MediaAsset::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where('original_name', 'ilike', "%{$search}%");
            })
            ->when($filters['mime'] ?? null, function ($query, string $mime): void {
                // Prefijo ("image/") o MIME exacto ("application/pdf").
                str_ends_with($mime, '/')
                    ? $query->where('mime_type', 'like', "{$mime}%")
                    : $query->where('mime_type', $mime);
            })
            ->when($filters['file_type_id'] ?? null, function ($query, int|string $typeId): void {
                $type = AllowedFileType::query()->find((int) $typeId);
                $query->whereIn('mime_type', $type?->mime_types ?? []);
            })
            ->latest()
            ->paginate(min((int) ($filters['per_page'] ?? 24), 100));
    }

    public function upload(UploadedFile $file, ?int $uploadedBy): MediaAsset
    {
        $type = $this->matchAllowedType($file);

        $disk = (string) config('filesystems.media_disk', 'public');
        // Nombre aleatorio (UUID permitido en valores no-clave): el filename
        // del cliente jamás llega al storage.
        $path = $file->storeAs(
            'media/'.now()->format('Y/m'),
            Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension()),
            $disk,
        );

        return MediaAsset::query()->create([
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => strtolower((string) $file->getMimeType()),
            'size' => $file->getSize(),
            'transformations' => null,
            'processing_status' => 'completed',
            'storage_provider_id' => null,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function delete(MediaAsset $asset): void
    {
        if ($asset->path !== '' && Storage::disk($asset->disk)->exists($asset->path)) {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        $asset->delete();
    }

    /**
     * Valida el archivo contra los tipos activos y devuelve el tipo que lo
     * admite. El MIME se obtiene por sniffing real del contenido (Symfony
     * MimeTypeGuesser), no de la extensión.
     *
     * @throws ValidationException
     */
    private function matchAllowedType(UploadedFile $file): AllowedFileType
    {
        if ($file->getSize() > self::MAX_SIZE_KB * 1024) {
            throw ValidationException::withMessages([
                'file' => ['El archivo excede el tamaño máximo permitido (20 MB).'],
            ]);
        }

        $mime = strtolower((string) $file->getMimeType());
        $extension = strtolower($file->getClientOriginalExtension());

        $match = AllowedFileType::query()->active()->get()->first(
            fn (AllowedFileType $type): bool => $type->allowsMime($mime) && $type->allowsExtension($extension),
        );

        if ($match === null) {
            $allowed = AllowedFileType::activeExtensions()->implode(', ');

            throw ValidationException::withMessages([
                'file' => ["Tipo de archivo no permitido ({$mime}, .{$extension}). Extensiones activas: {$allowed}."],
            ]);
        }

        return $match;
    }
}
