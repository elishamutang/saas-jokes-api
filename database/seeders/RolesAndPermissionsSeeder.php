<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $seedRolesAndPermissions = [
            [
                'name' => 'client',
                'level' => 100,
                'permissions' => [
                    'can create a joke', 'can read any joke', 'can browse all jokes', 'can search a joke',
                    'can edit own joke', 'can delete own joke', 'can read a category', 'can browse categories',
                    'can search a category', 'can vote', 'can remove own vote', 'can edit own vote',
                    'can read own user profile', 'can edit own user profile', 'can delete own user profile',
                ],
            ],
            [
                'name' => 'staff',
                'level' => 500,
                'permissions' => [
                    'can create a joke', 'can read any joke', 'can browse all jokes', 'can search a joke',
                    'can edit any joke', 'can delete any joke', 'can create a category', 'can read any category',
                    'can browse all categories', 'can edit any category', 'can delete any category', 'can search any category',
                    'can browse all users', 'can read any user', 'can edit client users only', 'can add client users only', 'can delete client users only',
                    'can search any user', 'can vote', 'can remove own vote', 'can edit own vote', 'can read own user profile',
                    'can edit own user profile', 'can delete own user profile', 'can logout client users only',
                    'can reset client user passwords only', 'can edit client user profile only', 'can mark a client user as banned or suspended',
                    'can revert a client user from suspended to active',
                ],
            ],
            [
                'name' => 'admin',
                'level' => 750,
                'permissions' => [
                    'can create a joke', 'can read any joke', 'can browse all jokes', 'can search a joke',
                    'can edit any joke', 'can delete any joke', 'can remove jokes', 'can restore jokes',
                    'can create a category', 'can read any category', 'can browse all categories', 'can edit any category',
                    'can delete any category', 'can search any category', 'can remove categories', 'can restore categories',
                    'can browse all users', 'can read any user', 'can edit any user', 'can delete client and staff users only', 'can create a user',
                    'can search any user', 'can vote', 'can remove own vote', 'can remove all votes from client or staff user', 'can read own user profile',
                    'can edit own user profile', 'can delete own user profile', 'can edit client or staff users only',
                    'can edit client or staff roles only', 'can mark a client or staff user as banned or suspended',
                    'can revert a client or staff user from banned to suspended', 'can revert a client or staff user from suspended to active',
                    'can logout all staff and client users', 'can reset client or staff user passwords', 'can browse all roles',
                    'can read any role', 'can edit any role', 'can create a role', 'can search any role', 'can browse all permissions',
                    'can read any permission', 'can edit any permission', 'can create a permission', 'can search any permission',
                    'can assign user role', 'can update user role', 'can delete user role',
                ],
            ],
            [
                'name' => 'super-admin',
                'level' => 999,
                'permissions' => [
                        // Super-Admin implemented using Gate::before to assign all permissions
                    ],
            ],
        ];

        // Merge permission array
        $combinedPermissions = array_merge(
            $seedRolesAndPermissions[0]['permissions'],
            $seedRolesAndPermissions[1]['permissions'],
            $seedRolesAndPermissions[2]['permissions'],
        );

        // Remove duplicates
        $allPermissions = array_unique($combinedPermissions);

        // Create permissions
        foreach ($allPermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        foreach ($seedRolesAndPermissions as $rolesAndPermissions) {
            $roleName = $rolesAndPermissions['name'];
            $roleLevel = $rolesAndPermissions['level'];
            $rolePermissions = $rolesAndPermissions['permissions'];

            // Create role
            $role = Role::updateOrCreate(
                ['name' => $roleName],
                ['level' => $roleLevel]
            );

            // Sync permissions with role that's not a super-admin
            if (!empty($rolePermissions)) {
                $role->syncPermissions($rolePermissions);
            }
        }
    }
}
