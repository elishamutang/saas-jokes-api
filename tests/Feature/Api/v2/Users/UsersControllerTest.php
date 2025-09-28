<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');


// Browse all users
test('get all users', function() {
    // Prepare users
    User::factory(10)->create();

    // Get users
    $response = $this->getJson('/api/v2/users');

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

test('get specific number of users', function() {
    // Prepare users
    User::factory(10)->create();

    // Get users
    $perPage = 2;
    $response = $this->getJson("/api/v2/users?per_page=$perPage");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->where('data.current_page', 1)
                ->where('data.per_page', $perPage)
                ->has('data.data', $perPage)
        );
});

test('search for user based on name or email', function() {
    // Prepare users
    $users = [
        [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ],
        [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
        ],
        [
            'name' => 'Jack Black',
            'email' => 'jack@example.com',
            'password' => 'password',
        ],
        [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ],
    ];

    foreach($users as $user) {
        User::create($user);
    }

    // Get user
    $searchKeyword = 'Doe';
    $response = $this->getJson("/api/v2/users?search=$searchKeyword");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->has('data.data', 2)
        );
});

// Read a single user
test('get a single user', function() {
    // Prepare user
    $user = User::factory()->create();

    // Mock result
    $data = [
        'success' => true,
        'message' => 'User retrieved successfully',
        'data' => $user->toArray(),
    ];

    // Get user
    $response = $this->getJson("/api/v2/users/1");

    // Assert
    $response->assertStatus(200)
        ->assertJson($data);
});

// TODO: Create a single user
test('create a single user', function() {
    // Pending
});

// TODO: Update a single user
test('update a single user', function() {
    // Pending
});

// TODO: Delete a single user
test('delete a single user', function() {
    // Pending
});
