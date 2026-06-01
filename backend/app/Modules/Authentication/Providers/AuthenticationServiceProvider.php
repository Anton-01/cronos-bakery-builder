<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Providers;

use App\Shared\Providers\ModuleServiceProvider;

class AuthenticationServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }
}
