<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Joke;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class JokeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Randomise the user
        $users = User::all()->pluck('id')->toArray();

        $seedJokes = [
            [
                'title' => 'Skeleton Fight',
                'content' => "Why don't skeletons fight each other? ".
                           "Because they don't have the guts.",
                'user_id' => $users[array_rand($users)],
                'categories' => ['Skeleton', 'Maths'],
            ],
            [
                'title' => 'Pirate Maths',
                'content' => 'What type of Maths are pirates best at?'.
                           'Algebra. Because they are good at finding X.',
                'user_id' => $users[array_rand($users)],
                'categories' => ['Pirate', 'Maths'],
            ],
            [
                'title' => 'SQL Bar',
                'content' => 'An SQL query walks into a bar, sees two tables, and asks: "Can I join you?"',
                'user_id' => $users[array_rand($users)],
                'categories' => ['SQL', 'Database', 'Programming', 'Maths'],
            ],
            [
                'title' => 'Light Bulb',
                'content' => 'How many programmers does it take to change a light bulb? None. That’s a hardware problem.',
                'user_id' => $users[array_rand($users)],
                'categories' => ['Hardware', 'Programming', 'Devices', 'Maths'],
            ],
            [
                'title' => 'Java Divorce',
                'content' => 'Why did the two Java methods get a divorce? Because they had constant arguments.',
                'user_id' => $users[array_rand($users)],
                'categories' => ['Java', 'Programming', 'Maths'],
            ],
            [
                'title' => 'Halloween vs Christmas',
                'content' => 'Why do programmers always mix up Christmas and Halloween? Because Dec 25 == Oct 31.',
                'user_id' => $users[array_rand($users)],
                'categories' => ['Programming', 'Dates', 'Logic', 'Maths'],
            ],
            [
                'title' => 'Cache Bankruptcy',
                'content' => 'Why did the edge server go bankrupt? Because it ran out of cache.',
                'user_id' => $users[array_rand($users)],
                'categories' => ['Server', 'Caching', 'Programming', 'Maths'],
            ],
        ];

        foreach ($seedJokes as $seedJoke) {

            $categoryList = $seedJoke['categories'] ?? ['Unknown'];
            unset($seedJoke['categories']);

            $joke = Joke::updateOrCreate(
                ['title' => $seedJoke['title']],
                ['content' => $seedJoke['content'],
                    'user_id' => $seedJoke['user_id']]
            );

            foreach ($categoryList as $category) {
                Category::updateOrCreate(['title' => $category]);
            }

            if (! empty($categoryList)) {
                $categoryIds = Category::whereIn('title', $categoryList)
                    ->get()
                    ->pluck('id')
                    ->toArray();
                $joke->categories()->sync($categoryIds);
            }

        }
    }
}
