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
                    'create a joke', 'read any joke', 'browse all jokes', 'search a joke',
                    'edit own joke', 'delete own joke', 'read any category', 'browse all categories',
                    'search any category', 'vote', 'remove own vote', 'edit own vote',
                    'read own user profile', 'edit own user profile', 'delete own user profile',
                ],
            ],
            [
                'name' => 'staff',
                'level' => 500,
                'permissions' => [
                    'create a joke', 'read any joke', 'browse all jokes', 'search a joke',
                    'edit any joke', 'delete any joke', 'create a category', 'read any category',
                    'browse all categories', 'edit any category', 'delete any category', 'search any category',
                    'browse all users', 'read any user', 'edit client users only', 'add client users only', 'delete client users only',
                    'search any user', 'vote', 'remove own vote', 'edit own vote', 'read own user profile',
                    'edit own user profile', 'delete own user profile', 'logout client users only',
                    'reset client user passwords only', 'edit client user profile only', 'mark a client user as banned or suspended',
                    'revert a client user from suspended to active', 'browse soft-deleted categories', 'restore soft-deleted categories',
                    'browse soft-deleted jokes', 'restore soft-deleted jokes',
                ],
            ],
            [
                'name' => 'admin',
                'level' => 750,
                'permissions' => [
                    'create a joke', 'read any joke', 'browse all jokes', 'search a joke',
                    'edit any joke', 'delete any joke', 'browse soft-deleted jokes', 'restore soft-deleted jokes', 'remove soft-deleted jokes',
                    'create a category', 'read any category', 'browse all categories', 'edit any category',
                    'delete any category', 'search any category', 'remove categories', 'restore categories',
                    'browse all users', 'read any user', 'edit any user', 'delete client and staff users only', 'create a user',
                    'search any user', 'vote', 'remove own vote', 'remove all votes from client or staff user', 'read own user profile',
                    'edit own user profile', 'delete own user profile', 'edit client or staff users only',
                    'edit client or staff roles only', 'mark a client or staff user as banned or suspended',
                    'revert a client or staff user from banned to suspended', 'revert a client or staff user from suspended to active',
                    'logout all staff and client users', 'reset client or staff user passwords', 'browse all roles',
                    'read any role', 'edit any role', 'create a role', 'search any role', 'delete any role', 'browse all permissions',
                    'read any permission', 'search any permission', 'assign user role', 'update user role', 'delete user role',
                    'browse soft-deleted categories', 'restore soft-deleted categories', 'remove soft-deleted categories',
                ],
            ],
            [
                'name' => 'super-admin',
                'level' => 999,
                'permissions' => [
                        // Super-Admin implemented globally using Gate::before to assign all permissions
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
