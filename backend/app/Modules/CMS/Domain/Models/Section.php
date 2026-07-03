<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Infrastructure\Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reusable, configurable block stored in the section library. It can be
 * referenced by many pages via {@see PageBlock}. A null brand_id marks a
 * global (platform-wide) section available to every brand.
 *
 * @property int $id
 * @property int|null $brand_id
 * @property string $name
 * @property BlockType $type
 * @property array<string, mixed> $data
 * @property bool $is_active
 */
class Section extends Model
{
    use HasFactory;

    protected $table = 'cms_sections';

    protected $fillable = [
        'brand_id',
        'name',
        'type',
        'data',
        'is_active',
    ];

    protected $casts = [
        'brand_id' => 'integer',
        'type' => BlockType::class,
        'data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    protected static function newFactory(): SectionFactory
    {
        return SectionFactory::new();
    }
}
