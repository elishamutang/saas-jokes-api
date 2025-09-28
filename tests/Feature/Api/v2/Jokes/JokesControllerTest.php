<?php

use App\Models\Joke;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

// Browse all jokes
test('get all jokes', function () {
    // Create jokes
    Joke::factory(5)->create();

    // Get all jokes
    $response = $this->getJson('/api/v2/jokes');

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->where('data.current_page', 1)
                ->where('data.per_page', 5)
                ->has('data.data', 5)
        );
});

test('get specific number of jokes per page', function () {
    // Create jokes
    Joke::factory(10)->create();

    // Get jokes
    $perPage = 2;
    $response = $this->getJson("/api/v2/jokes?per_page=$perPage");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->where('data.current_page', 1)
                ->where('data.per_page', 2)
                ->has('data.data', $perPage)
        );
});

test('search for a joke based on title', function () {
    // Create users
    User::factory(10)->create();

    // Create jokes
    $jokes = [
        [
            'title' => 'Joke 1',
            'content' => 'Joke 1 content',
            'user_id' => 1,
        ],
        [
            'title' => 'Joke 2',
            'content' => 'Joke 2 content',
            'user_id' => 2,
        ],
        [
            'title' => 'Joke 3',
            'content' => 'Joke 3 content',
            'user_id' => 3,
        ],
        [
            'title' => 'This is Joke 4',
            'content' => 'Joke 4 content',
            'user_id' => 4,
        ],
    ];

    foreach($jokes as $joke) {
        Joke::create($joke);
    }

    // Get joke
    $searchKeyword = 'this';
    $response = $this->getJson("/api/v2/jokes?search=$searchKeyword");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->where('data.data.0.title', 'This is Joke 4')
                ->has('data.data', 1)
        );
});

// Read a single joke
test('get a single joke', function () {
    // Create jokes
    Joke::factory(5)->create();

    $joke = Joke::limit(1)->get();
    $jokeId = $joke[0]->id;

    // Mock result
    $data = [
        'success' => true,
        'message' => "Joke retrieved successfully",
        'data' => $joke[0]->toArray(),
    ];

    // Get response
    $response = $this->getJson("/api/v2/jokes/$jokeId");

    // Assert
    $response->assertStatus(200)->assertJson($data);
});

// Update a single joke
test('update a single joke', function () {
    // Create users
    User::factory(5)->create();

    // Create joke
    Joke::create([
        'title' => fake()->word,
        'content' => fake()->text,
        'user_id' => 1,
    ]);

    // Update joke
    $updatedJoke = [
        'title' => 'Updated title',
        'content' => 'Updated content',
    ];

    // Mock result
    $result = [
        'success' => true,
        'message' => 'Joke updated successfully',
        'data' => $updatedJoke,
    ];

    // Response
    $response = $this->putJson('/api/v2/jokes/1', $updatedJoke);

    // Assert
    $response->assertStatus(200)
        ->assertJson($result);
});

// Add a single joke
test('add a joke', function () {
    // Populate users in DB
    User::factory(5)->create();

    // Create joke
    $joke = [
        'title' => 'New joke',
        'content' => 'New joke content',
        'user_id' => 1,
    ];

    Joke::create($joke);
    unset($joke['user_id']);

    // Mock result
    $result = [
        'success' => true,
        'message' => 'Joke created successfully',
        'data' => $joke,
    ];

    // Response
    $response = $this->postJson('/api/v2/jokes', $joke);

    // Assert
    $response->assertStatus(200)
        ->assertJson($result);
});

// Delete a single joke
test('delete a joke', function () {
    // Populate users in DB
    User::factory(5)->create();

    // Create jokes
    $jokes = [
        [
            'title' => fake()->word,
            'content' => fake()->text,
            'user_id' => 1,
        ],
        [
            'title' => fake()->word,
            'content' => fake()->text,
            'user_id' => 2,
        ],
    ];

    foreach($jokes as $joke) {
        Joke::create($joke);
    }

    // Joke ID to be deleted
    $jokeId = $jokes[1]['user_id'];

    // Response
    $response = $this->deleteJson('/api/v2/jokes/2');

    $response->assertStatus(204);

    // Verify company is no longer in the database
    $this->assertSoftDeleted('jokes', ['id' => $jokeId]);
});
