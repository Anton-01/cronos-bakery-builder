<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Providers;

use App\Shared\Providers\ModuleServiceProvider;

class ProductBuilderServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }
}
