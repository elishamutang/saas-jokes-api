<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(
            [
                // Seed roles and permissions before anything else.
                RolesAndPermissionsSeeder::class,
                UserSeeder::class,
                CategorySeeder::class,
                JokeSeeder::class,
            ]
        );

    }
}
