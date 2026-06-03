<?php

declare(strict_types=1);

namespace App\Shared\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base service provider for feature modules. It wires up the conventional
 * pieces of a module — routes, migrations, repository bindings, policies and
 * event listeners — so each concrete module provider only declares what is
 * specific to it.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Absolute path to the module root (the directory containing this module's
     * sub-folders). Concrete providers return `__DIR__ . '/..'` or similar.
     */
    abstract protected function modulePath(): string;

    /**
     * Repository interface => implementation bindings for the container.
     *
     * @return array<class-string, class-string>
     */
    protected function repositories(): array
    {
        return [];
    }

    /**
     * Eloquent model => Policy class mappings.
     *
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [];
    }

    /**
     * Domain event => array of listener classes.
     *
     * @return array<class-string, array<class-string>>
     */
    protected function listeners(): array
    {
        return [];
    }

    public function register(): void
    {
        foreach ($this->repositories() as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerPolicies();
        $this->registerListeners();
    }

    protected function registerRoutes(): void
    {
        $apiRoutes = $this->modulePath() . '/Presentation/Http/routes.php';

        if (is_file($apiRoutes)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($apiRoutes);
        }
    }

    protected function registerMigrations(): void
    {
        $migrations = $this->modulePath() . '/Infrastructure/Database/Migrations';

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }

    protected function registerPolicies(): void
    {
        foreach ($this->policies() as $model => $policy) {
            \Illuminate\Support\Facades\Gate::policy($model, $policy);
        }
    }

    protected function registerListeners(): void
    {
        foreach ($this->listeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                \Illuminate\Support\Facades\Event::listen($event, $listener);
            }
        }
    }
}
