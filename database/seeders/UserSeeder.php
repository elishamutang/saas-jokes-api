<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedUsers = [
            [
                'id' => 1,
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => 'Password1',
                'email_verified_at' => now(),
                'roles' => ['super-admin', 'admin'],
            ],

            [
                'id' => 2,
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => 'Password1',
                'email_verified_at' => now(),
                'roles' => ['admin'],
            ],

            [
                'id' => 3,
                'name' => 'Staff User',
                'email' => 'staff@example.com',
                'password' => 'Password1',
                'email_verified_at' => now(),
                'roles' => ['staff'],
            ],

            [
                'id' => 4,
                'name' => 'Client User',
                'email' => 'client@example.com',
                'password' => 'Password1',
                'email_verified_at' => now(),
                'roles' => ['client'],
            ],

            [
                'id' => 5,
                'name' => 'Client User 2',
                'email' => 'client2@example.com',
                'password' => 'Password1',
                'email_verified_at' => null,
                'roles' => ['client'],
            ],

            [
                'id' => 6,
                'name' => 'Client User 3',
                'email' => 'client3@example.com',
                'password' => 'Password1',
                'email_verified_at' => null,
                'roles' => ['client'],
            ],
        ];

        foreach ($seedUsers as $newUser) {
            // Grab user role
            $roles = $newUser['roles'];
            unset($newUser['roles']);

            // Randomly assign user status.
            $status = ['active', 'suspended', 'banned'];
            $randomStatusKey = array_rand($status);

            $newUser['status'] = $status[$randomStatusKey];

            // Update or create user in DB.
            $user = User::updateOrCreate(
                ['email' => $newUser['email']],
                $newUser
            );

            // Assign user role
             $user->assignRole($roles);
        }

    }
}
