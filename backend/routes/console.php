<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fire due pickup reminders (24h/12h/2h before) every hour.
Schedule::command('notifications:dispatch-reminders')->hourly()->withoutOverlapping();

// Automated backups: nightly database + files backup, weekly cleanup + monitor.
Schedule::command('backup:clean')->daily()->at('01:00')->withoutOverlapping();
Schedule::command('backup:run')->daily()->at('01:30')->withoutOverlapping();
Schedule::command('backup:monitor')->daily()->at('02:00');
