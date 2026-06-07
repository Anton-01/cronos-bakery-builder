<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Calendar\Infrastructure\Database\Seeders\CalendarSeeder;
use App\Modules\Catalog\Infrastructure\Database\Seeders\CatalogTaxonomySeeder;
use App\Modules\CMS\Infrastructure\Database\Seeders\CmsContentSeeder;
use App\Modules\CMS\Infrastructure\Database\Seeders\ThemeBuilderSeeder;
use App\Modules\Notifications\Infrastructure\Database\Seeders\NotificationSeeder;
use App\Modules\Orders\Infrastructure\Database\Seeders\BranchSeeder;
use App\Modules\Payments\Infrastructure\Database\Seeders\PaymentGatewaySeeder;
use App\Modules\ProductBuilder\Infrastructure\Database\Seeders\ProductBuilderSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== Iniciando el proceso de Seeding de Cronos ===');

        $this->command->comment('Cargando Roles y Permisos...');
        $this->call(RolesAndPermissionsSeeder::class);

        $this->call(CmsContentSeeder::class);
        $this->call(ThemeBuilderSeeder::class);
        $this->call(ProductBuilderSeeder::class);
        $this->call(CatalogTaxonomySeeder::class);
        $this->call(BranchSeeder::class);
        $this->call(CalendarSeeder::class);
        $this->call(PaymentGatewaySeeder::class);
        $this->call(NotificationSeeder::class);

        $this->command->newLine();
        $this->command->info('=== Creando Cuentas Administrativas Oficiales ===');

        // Super administrator
        $superAdminEmail = 'superadmin@cronos.test';
        $superAdmin = Admin::updateOrCreate(
            ['email' => $superAdminEmail],
            Admin::factory()->raw([
                'name' => 'Cronos Super Admin',
                'email' => $superAdminEmail
            ])
        );

        if (!$superAdmin->hasRole(AdminRole::SuperAdmin->value)) {
            $superAdmin->assignRole(AdminRole::SuperAdmin->value);
        }

        foreach (AdminRole::cases() as $role) {
            if ($role === AdminRole::SuperAdmin) {
                continue;
            }

            $roleEmail = "{$role->value}@cronos.test";

            $admin = Admin::updateOrCreate(
                ['email' => $roleEmail],
                Admin::factory()->raw([
                    'email' => $roleEmail
                ])
            );

            if (!$admin->hasRole($role->value)) {
                $admin->assignRole($role->value);
            }
        }

        // Sample customers
        if (User::count() === 0) {
            User::factory(5)->create();
        }

        $this->validateSeededData();
    }

    private function validateSeededData(): void
    {
        $this->command->newLine();
        $this->command->info('=== VERIFICACIÓN POST-SEEDING ===');

        $superAdminExists = Admin::where('email', 'superadmin@cronos.test')->exists();
        if ($superAdminExists) {
            $this->command->info('✔ [OK] El Super Admin (superadmin@cronos.test) está guardado correctamente en el sistema.');
        } else {
            $this->command->error('❌ [ALERTA] El Super Admin NO se encuentra en la base de datos.');
        }

        $actualCount = Admin::count();
        $this->command->info("✔ [OK] Total de administradores en el sistema: {$actualCount}.");
        $this->command->newLine();
    }
}
