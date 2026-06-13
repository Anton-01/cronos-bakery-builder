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
        $this->command->info('=== Starting the Cronos seeding process ===');

        $this->command->comment('Loading Roles and Permissions ...');
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
        $this->command->info('=== Creating Official Administrative Accounts ===');

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
        $this->command->info('=== POST-SEEDING VERIFICATION ===');

        $superAdminExists = Admin::where('email', 'superadmin@cronos.test')->exists();
        if ($superAdminExists) {
            $this->command->info('[OK] :: The Super Admin (superadmin@cronos.test) is correctly stored in the system.');
        } else {
            $this->command->error('[ALERT] :: The Super Admin is NOT in the database.');
        }

        $actualCount = Admin::count();
        $this->command->info("[OK] :: Total number of administrators in the system: {$actualCount}.");
        $this->command->newLine();
    }
}
