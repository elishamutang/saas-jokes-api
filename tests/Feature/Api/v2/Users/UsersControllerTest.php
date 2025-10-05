<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\Fluent\AssertableJson;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');


// Browse all users
test('get all users', function() {
    // Prepare users
    User::factory(10)->create();

    // Create authenticated user
    $user = User::first();
    $user->update([
        'email_verified_at' => now(),
    ]);
    $user->refresh();

    $this->actingAs($user);

    // Get users
    $response = $this->getJson('/api/v2/admin/users');

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

    // Create authenticated user
    $user = User::first();
    $user->update([
        'email_verified_at' => now(),
    ]);
    $user->refresh();

    $this->actingAs($user);

    // Get users
    $perPage = 2;
    $response = $this->getJson("/api/v2/admin/users?per_page=$perPage");

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

    // Create authenticated user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($authUser);

    // Get user
    $searchKeyword = 'Doe';
    $response = $this->getJson("/api/v2/admin/users?search=$searchKeyword");

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
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    // Mock result
    $data = [
        'success' => true,
        'message' => 'User retrieved successfully',
        'data' => $user->toArray(),
    ];

    // Get user
    $response = $this->getJson("/api/v2/admin/users/1");

    // Assert
    $response->assertStatus(200)
        ->assertJson($data);
});

// Create a single user
test('create a single user', function() {
    // Prepare user to be created
    $user = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password1',
    ];

    // Act as another authenticated user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($authUser);

    // Create user
    $response = $this->postJson("/api/v2/admin/users", $user);

    // Assert
    $response->assertStatus(200);

    $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
});

// Update an existing user's name and email
test("update an existing user's name and email", function() {
    // Prepare user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    // Get user id
    $userId = $user->id;

    // Prepare updated data
    $updatedUser = [
        'name' => 'Updated user',
        'email' => 'updated@example.com',
    ];

    // Update user
    $response = $this->putJson("/api/v2/admin/users/$userId", $updatedUser);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $updatedUser,
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $userId,
        'name' => $updatedUser['name'],
        'email' => $updatedUser['email'],
    ]);
});

// Update an existing user's password
test("update an existing user's password", function() {
    // Prepare user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => Hash::make('oldpassword'),
    ]);

    $this->actingAs($user);

    // Get user id
    $userId = $user->id;

    // Prepare updated password
    $newPassword = 'newpassword';

    // Update user
    $response = $this->putJson("/api/v2/admin/users/$userId", ['password' => $newPassword]);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "User updated successfully",
        ]);

    // Reload from database because $user variable still holds old data in memory.
    $user->refresh();

    // Assert password was changed and hashed properly
    assert(Hash::check($newPassword, $user->password));
});

// Delete a single user
test('delete a single user', function() {
    // Prepare users
    User::factory(5)->create();

    // Get user to be deleted
    $user = User::first();

    // Authenticate user
    $user->update(['email_verified_at' => now()]);
    $user->refresh();

    $userId = $user->id;

    $this->actingAs($user);

    // Delete user
    $response = $this->deleteJson("/api/v2/admin/users/$userId");

    // Assert
    $response->assertStatus(200);

    $this->assertSoftDeleted('users', ['id' => $userId]);
});
