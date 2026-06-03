<?php

declare(strict_types=1);

namespace App\Modules\Administration\Providers;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class AdministrationServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }

    public function boot(): void
    {
        parent::boot();

        // Super Admins bypass every permission check.
        Gate::before(function ($user, string $ability): ?bool {
            if ($user instanceof Admin && $user->hasRole(AdminRole::SuperAdmin->value)) {
                return true;
            }

            return null;
        });
    }
}
