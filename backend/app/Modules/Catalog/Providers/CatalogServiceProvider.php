<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Providers;

use App\Modules\Catalog\Domain\Events\ProductCreated;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Policies\ProductPolicy;
use App\Modules\Catalog\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Listeners\IndexProductOnCreation;
use App\Modules\Catalog\Infrastructure\Repositories\EloquentProductRepository;
use App\Shared\Providers\ModuleServiceProvider;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }

    protected function repositories(): array
    {
        return [
            ProductRepositoryInterface::class => EloquentProductRepository::class,
        ];
    }

    protected function policies(): array
    {
        return [
            Product::class => ProductPolicy::class,
        ];
    }

    protected function listeners(): array
    {
        return [
            ProductCreated::class => [IndexProductOnCreation::class],
        ];
    }
}
