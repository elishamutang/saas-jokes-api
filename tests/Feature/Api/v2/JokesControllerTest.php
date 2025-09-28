<?php

use App\Models\Joke;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

// Browse all jokes
test('get all jokes', function () {
    // Create users
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

    // Mock result
    $result = [
        'success' => true,
        'message' => 'Jokes retrieved successfully',
        'data' => $jokes,
    ];

    // Get response
    $response = $this->getJson('/api/v2/jokes');

    // Assert
    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJson($result);
});

// Read a single joke
test('get a single joke', function () {
    // Create users
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

    // Mock result
    $result = [
        'success' => true,
        'message' => 'Joke retrieved successfully',
        'data' => $jokes[1],
    ];

    // Get response
    $response = $this->getJson('/api/v2/jokes/2');

    // Assert
    $response->assertStatus(200)->assertJson($result);
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
