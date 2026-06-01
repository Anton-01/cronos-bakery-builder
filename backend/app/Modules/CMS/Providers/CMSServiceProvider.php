<?php

declare(strict_types=1);

namespace App\Modules\CMS\Providers;

use App\Modules\CMS\Domain\Repositories\PageRepositoryInterface;
use App\Modules\CMS\Infrastructure\Repositories\EloquentPageRepository;
use App\Shared\Providers\ModuleServiceProvider;

class CMSServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }

    protected function repositories(): array
    {
        return [
            PageRepositoryInterface::class => EloquentPageRepository::class,
        ];
    }
}
