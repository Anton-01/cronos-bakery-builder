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
