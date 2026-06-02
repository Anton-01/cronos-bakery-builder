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
        // Admin roles & permissions first.
        $this->call(RolesAndPermissionsSeeder::class);

        // Super administrator.
        $superAdmin = Admin::factory()->create([
            'name' => 'Cronos Super Admin',
            'email' => 'superadmin@cronos.test',
        ]);
        $superAdmin->assignRole(AdminRole::SuperAdmin->value);

        // One administrator per remaining role for convenience.
        foreach (AdminRole::cases() as $role) {
            if ($role === AdminRole::SuperAdmin) {
                continue;
            }

            Admin::factory()
                ->create(['email' => "{$role->value}@cronos.test"])
                ->assignRole($role->value);
        }

        // Sample customers.
        User::factory(5)->create();

        // Dynamic CMS pages with builder blocks.
        $this->call(CmsContentSeeder::class);

        // Theme Builder: active theme, navigation menu and banners.
        $this->call(ThemeBuilderSeeder::class);

        // Product Builder: configurable cakes with options, pricing and rules.
        $this->call(ProductBuilderSeeder::class);

        // Catalog: categories, collections, attributes and classified products.
        $this->call(CatalogTaxonomySeeder::class);

        // Orders: pickup branches (sucursales).
        $this->call(BranchSeeder::class);

        // Calendar: scheduling engine configuration (schedule, slots, rules).
        $this->call(CalendarSeeder::class);

        // Payments: multi-gateway configuration (sandbox).
        $this->call(PaymentGatewaySeeder::class);
    }
}
