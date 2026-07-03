<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Infrastructure\Database\Factories\PageBlockFactory;
use App\Shared\Domain\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single ordered block within a page. Either inline (its own type + data) or
 * a reference to a reusable {@see Section}, in which case the section's data is
 * used as the base and any local data overrides it.
 *
 * @property int $id
 * @property int $page_id
 * @property int|null $section_id
 * @property BlockType $type
 * @property array<string, mixed>|null $data
 * @property int $position
 * @property bool $is_active
 */
class PageBlock extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'cms_page_blocks';

    protected $fillable = [
        'page_id',
        'section_id',
        'type',
        'data',
        'position',
        'is_active',
    ];

    protected $casts = [
        'page_id' => 'integer',
        'section_id' => 'integer',
        'type' => BlockType::class,
        'data' => 'array',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * The effective block type: a referenced section dictates its own type.
     */
    public function resolvedType(): BlockType
    {
        return $this->section?->type ?? $this->type;
    }

    /**
     * The effective data payload: reusable section data as the base, overridden
     * by any inline data set on this block.
     *
     * @return array<string, mixed>
     */
    public function resolvedData(): array
    {
        $base = $this->section?->data ?? [];

        return array_merge($base, $this->data ?? []);
    }

    protected static function newFactory(): PageBlockFactory
    {
        return PageBlockFactory::new();
    }
}
