<?php

declare(strict_types=1);

return [
    App\Modules\Administration\Providers\AdministrationServiceProvider::class,
    App\Modules\Authentication\Providers\AuthenticationServiceProvider::class,
    App\Modules\CMS\Providers\CMSServiceProvider::class,
    App\Modules\Calendar\Providers\CalendarServiceProvider::class,
    App\Modules\Catalog\Providers\CatalogServiceProvider::class,
    App\Modules\Notifications\Providers\NotificationsServiceProvider::class,
    App\Modules\Orders\Providers\OrdersServiceProvider::class,
    App\Modules\Payments\Providers\PaymentsServiceProvider::class,
    App\Modules\ProductBuilder\Providers\ProductBuilderServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
];
