<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create(
            [
                'id' => 1,
                'title' => 'Unknown',
                'description' => 'Sorry, but we have no idea where to place this joke.',
            ]
        );

        $seedCategories = [
            [
                'title' => 'Dad',
                'description' => 'Dad jokes are always the most puntastic and groan worthy!',
            ],
            [
                'title' => 'Pun',
                'description' => "Simply so punny you'll have to laugh",
            ],
            [
                'title' => 'Pirate',
                'description' => 'Aaaaarrrrrrrrgh, me hearties!',
            ],
            [
                'title' => 'Skeleton',
                'description' => 'Jokes about skeletons and bones.',
            ],
            [
                'title' => 'Maths',
                'description' => 'Jokes related to mathematics.',
            ],
            [
                'title' => 'Programming',
                'description' => 'Jokes related to programming and software development.',
            ],
            [
                'title' => 'SQL',
                'description' => 'Database and SQL related humor.',
            ],
            [
                'title' => 'Hardware',
                'description' => 'Jokes about computer hardware and devices.',
            ],
            [
                'title' => 'Java',
                'description' => 'Jokes related to the Java programming language.',
            ],
            [
                'title' => 'Dates',
                'description' => 'Jokes related to date/time and formatting.',
            ],
            [
                'title' => 'Logic',
                'description' => 'Logical and programming-related humor.',
            ],
            [
                'title' => 'Server',
                'description' => 'Jokes about servers and hosting.',
            ],
            [
                'title' => 'Caching',
                'description' => 'Humor based on caching mechanisms.',
            ],
        ];

        // Shuffle the categories for fun ;)
        shuffle($seedCategories);

        foreach ($seedCategories as $seedCategory) {
            Category::create($seedCategory);
        }

        // Category::factory(10)->create();

    }
}
