<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\Services\TaxonomyService;
use App\Modules\Catalog\Presentation\Http\Requests\SyncProductTaxonomyRequest;
use App\Modules\Catalog\Presentation\Http\Resources\CatalogProductResource;
use Illuminate\Routing\Controller;

/**
 * Assigns a product's categories, collections, attribute values and tags.
 */
class ProductTaxonomyController extends Controller
{
    public function __construct(private readonly TaxonomyService $taxonomy)
    {
    }

    public function update(SyncProductTaxonomyRequest $request, string $product): CatalogProductResource
    {
        return new CatalogProductResource(
            $this->taxonomy->syncProductTaxonomy($product, $request->validated()),
        );
    }
}
