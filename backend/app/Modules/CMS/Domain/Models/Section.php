<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Infrastructure\Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A reusable, configurable block stored in the section library. It can be
 * referenced by many pages via {@see PageSection}.
 *
 * @property string $id
 * @property string $name
 * @property BlockType $type
 * @property array<string, mixed> $data
 * @property bool $is_active
 */
class Section extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'cms_sections';

    protected $fillable = [
        'name',
        'type',
        'data',
        'is_active',
    ];

    protected $casts = [
        'type' => BlockType::class,
        'data' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): SectionFactory
    {
        return SectionFactory::new();
    }
}
