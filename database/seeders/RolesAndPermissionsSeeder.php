<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed roles and permissions for the application.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AccessControl::userPermissions() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (AccessControl::postPermissions() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (AccessControl::posPermissions() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => AccessControl::ROLE_SUPER_ADMIN,
            'guard_name' => 'web',
        ]);

        $administrator = Role::firstOrCreate([
            'name' => AccessControl::ROLE_ADMINISTRATOR,
            'guard_name' => 'web',
        ]);

        $cashier = Role::firstOrCreate([
            'name' => AccessControl::ROLE_CASHIER,
            'guard_name' => 'web',
        ]);

        $subscriber = Role::firstOrCreate([
            'name' => AccessControl::ROLE_SUBSCRIBER,
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions([]);
        $administrator->syncPermissions([
            ...AccessControl::userPermissions(),
            ...AccessControl::postPermissions(),
            ...AccessControl::posPermissions(),
        ]);
        $cashier->syncPermissions(AccessControl::cashierPosPermissions());
        $subscriber->syncPermissions([AccessControl::PERMISSION_POSTS_VIEW]);

        User::doesntHave('roles')->each(
            fn (User $user) => $user->assignRole(AccessControl::ROLE_SUBSCRIBER),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
