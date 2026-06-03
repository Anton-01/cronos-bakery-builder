<?php

declare(strict_types=1);

namespace App\Modules\Payments\Providers;

use App\Shared\Providers\ModuleServiceProvider;

class PaymentsServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }
}
