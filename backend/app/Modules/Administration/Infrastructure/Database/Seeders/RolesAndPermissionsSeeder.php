<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Database\Seeders;

use App\Modules\Administration\Domain\Enums\AdminRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the granular permission set and the six administrative roles on the
 * dedicated `admin` guard, wiring each role to the permissions it needs.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * Master list of granular permissions.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'view dashboard',
        'manage admins',
        'manage roles',
        'manage products',
        'view products',
        'manage orders',
        'view orders',
        'update order status',
        'manage payments',
        'manage production',
        'view production',
        'manage calendar',
        'manage cms',
        'manage theme',
        'manage marketing',
        'manage notifications',
        'manage deliveries',
        'view deliveries',
        'view reports',
    ];

    /**
     * Role => permissions mapping. Super Admin is granted everything via a
     * Gate::before rule (see AdministrationServiceProvider), so it is omitted.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        AdminRole::Administrator->value => [
            'view dashboard', 'manage products', 'view products', 'manage orders',
            'view orders', 'update order status', 'manage payments', 'manage production',
            'view production', 'manage calendar', 'manage cms', 'manage theme', 'manage marketing',
            'manage notifications', 'manage deliveries', 'view deliveries', 'view reports',
        ],
        AdminRole::Production->value => [
            'view dashboard', 'view products', 'manage production', 'view production',
            'manage calendar', 'view orders',
        ],
        AdminRole::Sales->value => [
            'view dashboard', 'view products', 'manage orders', 'view orders',
            'update order status', 'view reports',
        ],
        AdminRole::Marketing->value => [
            'view dashboard', 'manage cms', 'manage theme', 'manage marketing',
            'manage notifications', 'view products', 'view reports',
        ],
        AdminRole::Courier->value => [
            'view dashboard', 'view deliveries', 'manage deliveries', 'view orders',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        // Ensure every role exists, even Super Admin (permissions via Gate::before).
        foreach (AdminRole::values() as $roleName) {
            $role = Role::findOrCreate($roleName, self::GUARD);
            $role->syncPermissions(self::ROLE_PERMISSIONS[$roleName] ?? []);
        }
    }
}
