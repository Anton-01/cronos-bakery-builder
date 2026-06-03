<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Providers;

use App\Modules\ProductBuilder\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\ProductBuilder\Infrastructure\Repositories\EloquentProductRepository;
use App\Shared\Providers\ModuleServiceProvider;

class ProductBuilderServiceProvider extends ModuleServiceProvider
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
}
