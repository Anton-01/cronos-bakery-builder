<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use App\Modules\CMS\Infrastructure\Database\Factories\ThemeFactory;
use App\Shared\Domain\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Branding configuration for the storefront. A single theme is active at a
 * time and drives colours, typography, logo, favicon and footer.
 *
 * @property int $id
 * @property string $name
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property array<string, string> $colors
 * @property array<string, mixed> $fonts
 * @property array<string, mixed>|null $footer
 * @property array<string, mixed>|null $settings
 * @property bool $is_active
 */
class Theme extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'themes';

    protected $fillable = [
        'name',
        'logo_path',
        'favicon_path',
        'colors',
        'fonts',
        'footer',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'colors' => 'array',
        'fonts' => 'array',
        'footer' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ThemeFactory
    {
        return ThemeFactory::new();
    }
}
