<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers;

use App\Modules\CMS\Application\Services\ThemeService;
use App\Modules\CMS\Presentation\Http\Resources\ThemeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Exposes the active branding theme for the storefront to render dynamically.
 */
class ThemeController extends Controller
{
    public function __construct(private readonly ThemeService $themes)
    {
    }

    public function show(): JsonResponse|ThemeResource
    {
        $theme = $this->themes->active();

        if ($theme === null) {
            return response()->json(['data' => null]);
        }

        return new ThemeResource($theme);
    }
}
