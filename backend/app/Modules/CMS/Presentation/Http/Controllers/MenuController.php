<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers;

use App\Modules\CMS\Application\Services\MenuService;
use App\Modules\CMS\Domain\Enums\MenuLocation;
use App\Modules\CMS\Presentation\Http\Resources\MenuResource;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public read access to the active menu for a given location (header/footer).
 */
class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menus)
    {
    }

    public function show(string $location): MenuResource
    {
        $enum = MenuLocation::tryFrom($location);
        $menu = $enum !== null ? $this->menus->activeForLocation($enum) : null;

        if ($menu === null) {
            throw new NotFoundHttpException('Menu not found.');
        }

        return new MenuResource($menu);
    }
}
