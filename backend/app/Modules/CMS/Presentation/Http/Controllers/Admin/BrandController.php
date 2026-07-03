<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Domain\Models\Brand;
use App\Modules\CMS\Presentation\Http\Resources\BrandResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * Read access to the brands (tenants) an administrator can manage content
 * for. Brand provisioning itself is an operations concern outside the CMS.
 */
class BrandController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BrandResource::collection(
            Brand::query()->active()->orderBy('name')->get(),
        );
    }
}
