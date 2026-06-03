<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Providers;

use App\Modules\Notifications\Domain\Events\AutomationTriggered;
use App\Modules\Notifications\Infrastructure\Console\DispatchRemindersCommand;
use App\Modules\Notifications\Infrastructure\Listeners\HandleAutomationTrigger;
use App\Shared\Providers\ModuleServiceProvider;

class NotificationsServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }

    protected function listeners(): array
    {
        return [
            AutomationTriggered::class => [HandleAutomationTrigger::class],
        ];
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([DispatchRemindersCommand::class]);
        }
    }
}
