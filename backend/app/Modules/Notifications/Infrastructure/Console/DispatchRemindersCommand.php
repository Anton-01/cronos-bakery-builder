<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Infrastructure\Console;

use App\Modules\Notifications\Application\Services\ReminderService;
use Illuminate\Console\Command;

/**
 * Scheduled command (hourly) that fires due pickup reminders. Registered with
 * the scheduler in routes/console.php.
 */
class DispatchRemindersCommand extends Command
{
    protected $signature = 'notifications:dispatch-reminders';

    protected $description = 'Dispatch due pickup reminders (24h/12h/2h before).';

    public function handle(ReminderService $reminders): int
    {
        $count = $reminders->dispatchDue();

        $this->info("Dispatched {$count} reminder(s).");

        return self::SUCCESS;
    }
}
