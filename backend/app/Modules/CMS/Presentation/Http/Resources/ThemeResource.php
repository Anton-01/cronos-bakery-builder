<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Resources;

use App\Modules\CMS\Domain\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Theme
 */
class ThemeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo_path,
            'favicon' => $this->favicon_path,
            'colors' => $this->colors,
            'fonts' => $this->fonts,
            'footer' => $this->footer,
            'is_active' => $this->is_active,
        ];
    }
}
