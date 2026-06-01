<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Authentication\Domain\Models\User;
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
    }
}
