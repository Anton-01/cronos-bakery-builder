<?php

declare(strict_types=1);

namespace App\Modules\CMS\Providers;

use App\Shared\Providers\ModuleServiceProvider;

class CMSServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }
}
