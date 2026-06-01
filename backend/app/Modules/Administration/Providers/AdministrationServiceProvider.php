<?php

declare(strict_types=1);

namespace App\Modules\Administration\Providers;

use App\Shared\Providers\ModuleServiceProvider;

class AdministrationServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }
}
