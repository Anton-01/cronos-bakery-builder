<?php

declare(strict_types=1);

use App\Modules\Administration\Providers\AdministrationServiceProvider;
use App\Modules\Authentication\Providers\AuthenticationServiceProvider;
use App\Modules\Calendar\Providers\CalendarServiceProvider;
use App\Modules\Catalog\Providers\CatalogServiceProvider;
use App\Modules\CMS\Providers\CMSServiceProvider;
use App\Modules\Notifications\Providers\NotificationsServiceProvider;
use App\Modules\Orders\Providers\OrdersServiceProvider;
use App\Modules\Payments\Providers\PaymentsServiceProvider;
use App\Modules\ProductBuilder\Providers\ProductBuilderServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Feature modules (Domain-Driven Design bounded contexts)
    AuthenticationServiceProvider::class,
    CMSServiceProvider::class,
    CatalogServiceProvider::class,
    ProductBuilderServiceProvider::class,
    OrdersServiceProvider::class,
    PaymentsServiceProvider::class,
    CalendarServiceProvider::class,
    NotificationsServiceProvider::class,
    AdministrationServiceProvider::class,
];
