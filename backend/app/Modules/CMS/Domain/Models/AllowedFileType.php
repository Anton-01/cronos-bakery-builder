<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Tipo de archivo permitido en la Media Library. El administrador enciende o
 * apaga tipos (`is_active`); la validación de subidas se calcula SIEMPRE de
 * forma dinámica contra las filas activas (mime real + extensión).
 *
 * @property int $id
 * @property string $name
 * @property string $category
 * @property string|null $description
 * @property array<int, string> $mime_types
 * @property array<int, string> $extensions
 * @property string $icon_reference
 * @property bool $is_active
 */
class AllowedFileType extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description',
        'mime_types',
        'extensions',
        'icon_reference',
        'is_active',
    ];

    protected $casts = [
        'mime_types' => 'array',
        'extensions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @param  Builder<AllowedFileType>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function allowsMime(string $mime): bool
    {
        return in_array(strtolower($mime), array_map('strtolower', $this->mime_types), true);
    }

    public function allowsExtension(string $extension): bool
    {
        return in_array(strtolower(ltrim($extension, '.')), array_map('strtolower', $this->extensions), true);
    }

    /**
     * MIME types de todos los tipos activos (para validación dinámica).
     *
     * @return Collection<int, string>
     */
    public static function activeMimeTypes(): Collection
    {
        return static::query()->active()->pluck('mime_types')
            ->flatten()
            ->map(static fn ($mime): string => strtolower((string) $mime))
            ->unique()
            ->values();
    }

    /**
     * Extensiones de todos los tipos activos (para validación dinámica).
     *
     * @return Collection<int, string>
     */
    public static function activeExtensions(): Collection
    {
        return static::query()->active()->pluck('extensions')
            ->flatten()
            ->map(static fn ($ext): string => strtolower((string) $ext))
            ->unique()
            ->values();
    }
}
