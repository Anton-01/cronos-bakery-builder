<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Providers;

use App\Shared\Providers\ModuleServiceProvider;

class CalendarServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }
}
