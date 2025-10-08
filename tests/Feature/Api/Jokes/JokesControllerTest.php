<?php

use App\Models\Category;
use App\Models\Joke;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\Fluent\AssertableJson;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

beforeEach(function() {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// Browse all jokes
test('client users can get all jokes', function () {
    // Create jokes
    Joke::factory(5)->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign client role
    $user->assignRole('client');
    $this->actingAs($user);

    // Get all jokes
    $response = $this->getJson('/api/jokes');

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

test('client users can get specific number of jokes per page', function () {
    // Create jokes
    Joke::factory(10)->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign client role
    $user->assignRole('client');
    $this->actingAs($user);

    // Get jokes
    $perPage = 2;
    $response = $this->getJson("/api/jokes?per_page=$perPage");

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

test('client users can search for a joke based on title', function () {
    // Create users
    User::factory(10)->create();

    // Create authenticated user and assign role.
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('client');

    $this->actingAs($user);

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
    $response = $this->getJson("/api/jokes?search=$searchKeyword");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->where('data.data.0.title', 'This is Joke 4')
                ->has('data.data', 1)
        );
});

// Unauthenticated users cannot read a single joke
test('unauthenticated users cannot read a single joke', function() {
    // Prepare data
    $joke = Joke::factory()->create();
    $category = Category::factory()->create();

    $joke->categories()->attach($category->id);

    // Get response
    $response = $this->getJson("/api/jokes/{$joke->id}");

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => "Please log into your account.",
        ]);
});

// Client users cannot read joke with unknown category
test('client users cannot read a joke with an unknown category', function() {
    // Prepare data
    $joke = Joke::factory()->create();
    $category = Category::factory()->create(['title' => 'Unknown']);

    $joke->categories()->attach($category->id);

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign role
    $user->assignRole('client');
    $this->actingAs($user);

    // Get response
    $response = $this->getJson("/api/jokes/{$joke->id}");

    $response->assertStatus(404)
        ->assertJson([
            'message' => "Joke not found"
        ]);
});

// Client users cannot read joke with empty category
test('client users cannot read a joke with empty category', function() {
    // Prepare data
    $joke = Joke::factory()->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign role
    $user->assignRole('client');
    $this->actingAs($user);

    // Get response
    $response = $this->getJson("/api/jokes/{$joke->id}");

    $response->assertStatus(404)
        ->assertJson([
            'message' => "Joke not found"
        ]);
});

// Create a single joke
test('client users can create a joke', function() {
    // Prepare
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $user->assignRole('client');
    $this->actingAs($user);

    $data = [
        'title' => 'joke',
        'content' => 'joke content',
    ];

    // Send POST request
    $response = $this->postJson('/api/jokes', $data);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Joke created successfully",
            'data' => $data,
        ]);
});


// Read a single joke
test('client users can read a single joke', function () {
    // Prepare data
    $joke = Joke::factory()->create();
    $category = Category::factory()->create();

    $joke->categories()->attach($category->id);

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign role
    $user->assignRole('client');
    $this->actingAs($user);

    // Mock result
    $data = [
        'success' => true,
        'message' => "Joke retrieved successfully",
        'data' => Joke::with(['categories', 'votes'])->find($joke->id)->toArray(),
    ];

    // Get response
    $response = $this->getJson("/api/jokes/{$joke->id}");

    // Assert
    $response->assertStatus(200)->assertJson($data);
});

// Client users cannot update other user jokes
test('client users cannot update other user jokes', function () {
    // Prepare
    User::factory(3)->create();

    // Create user and authenticate them.
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('client');
    $this->actingAs($user);

    // Create joke
    Joke::create([
        'title' => fake()->word,
        'content' => fake()->text,
        'user_id' => 3,
    ]);

    // Update joke
    $updatedJoke = [
        'title' => 'Updated title',
        'content' => 'Updated content',
    ];

    // Response
    $response = $this->putJson('/api/jokes/1', $updatedJoke);

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'message' => 'This action is unauthorized.'
        ]);
});

// Client user can update their own jokes
test('client users can update their own jokes', function() {
    // Create user and authenticate them.
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('client');
    $this->actingAs($user);

    // Create joke
    Joke::create([
        'title' => fake()->word,
        'content' => fake()->text,
        'user_id' => $user->id,
    ]);

    // Update joke
    $updatedJoke = [
        'title' => 'Updated title',
        'content' => 'Updated content',
    ];

    // Response
    $response = $this->putJson('/api/jokes/1', $updatedJoke);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Joke updated successfully",
            'data' => $updatedJoke,
        ]);
});

// Jokes removed when user chooses to delete their own profile
test('jokes are removed if user deletes their own profile', function() {
    // Prepare
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active'
    ]);

    $user->jokes()->create([
        'title' => 'joke',
        'content' => 'joke content',
    ]);

    $user->assignRole('client');
    $this->actingAs($user);

    // Send DELETE request
    $response = $this->deleteJson('/api/profile/delete');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Profile deleted successfully",
            'data' => [],
        ]);

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);

    $this->assertDatabaseMissing('jokes', [
        'title' => 'joke',
    ]);
});

// Staff level and higher can update other users jokes
test('staff level and higher can update other user jokes', function() {
    // Prepare
    User::factory(3)->create();

    // Create user and authenticate them.
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('staff');
    $this->actingAs($user);

    // Create joke
    Joke::create([
        'title' => fake()->word,
        'content' => fake()->text,
        'user_id' => 3,
    ]);

    // Update joke
    $updatedJoke = [
        'title' => 'Updated title',
        'content' => 'Updated content',
    ];

    // Response
    $response = $this->putJson('/api/jokes/1', $updatedJoke);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Joke updated successfully",
            'data' => $updatedJoke,
        ]);
});

// Unauthenticated users cannot add a joke
test('unauthenticated users cannot add a joke', function() {
    // Create joke
    $joke = [
        'title' => 'New joke',
        'content' => 'New joke content',
        'user_id' => 1,
    ];

    // Response
    $response = $this->postJson('/api/jokes', $joke);

    // Assert
    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => "Please log into your account."
        ]);
});

// Add a single joke
test('client users can add a joke', function () {
    // Create authenticated user
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('client');
    $this->actingAs($user);

    // Prepare joke
    $joke = [
        'title' => 'New joke',
        'content' => 'New joke content',
        'user_id' => 1,
    ];

    // Mock result
    $result = [
        'success' => true,
        'message' => 'Joke created successfully',
        'data' => $joke,
    ];

    // Response
    $response = $this->postJson('/api/jokes', $joke);

    // Assert
    $response->assertStatus(200)
        ->assertJson($result);
});

// Delete a single joke
test("client users cannot delete other user's joke", function () {
    // Populate users in DB
    User::factory(5)->create();

    // Create authenticated user
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('client');
    $this->actingAs($user);

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
    $response = $this->deleteJson("/api/jokes/$jokeId");

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
        ]);
});

// Client can delete their own jokes
test('client users can delete their own jokes', function() {
    $user = User::factory()->create();
    $user->assignRole('client');
    $this->actingAs($user);

    // Prepare joke
    $joke = Joke::create([
        'title' => 'New joke',
        'content' => 'New joke content',
        'user_id' => $user->id,
    ]);

    // Mock result
    $result = [
        'success' => true,
        'message' => 'Joke deleted successfully',
        'data' => [],
    ];

    // Response
    $response = $this->deleteJson("/api/jokes/{$joke->id}");

    $response->assertStatus(200)
        ->assertJson($result);

    // Verify company is no longer in the database
    $this->assertSoftDeleted('jokes', ['id' => $joke->id]);
});

// Client users cannot access jokes trash
test("client users cannot access jokes trash", function() {
    $user = User::factory()->create();
    $user->assignRole('client');
    $this->actingAs($user);

    // Response
    $response = $this->getJson("/api/jokes/trash");

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => [],
        ]);
});

// Staff level and higher can access jokes trash
test("staff level and higher can access jokes trash", function() {
    $user = User::factory()->create();
    $user->assignRole('staff');
    $this->actingAs($user);

    // Response
    $response = $this->getJson("/api/jokes/trash");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Deleted jokes retrieved successfully"
        ]);
});

// Staff level and higher can delete other user's jokes
test("staff level and higher can delete other user's jokes", function() {
    // Populate users in DB
    User::factory(5)->create();

    // Create authenticated user
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('staff');
    $this->actingAs($user);

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
    $response = $this->deleteJson("/api/jokes/$jokeId");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Joke deleted successfully",
            'data' => [],
        ]);
});

// Staff level and higher can recover deleted jokes
test("staff level and higher can recover all deleted jokes", function() {
    // Prepare jokes and delete them
    Joke::factory(2)->create();
    Joke::query()->delete();

    // Authenticate user
    $user = User::factory()->create();
    $user->assignRole('staff');
    $this->actingAs($user);

    // Recover jokes
    $response = $this->postJson("/api/jokes/trash/recover-all");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "2 jokes recovered successfully"
        ]);
});

test("staff level can recover one deleted joke", function() {
    $joke = Joke::factory()->create();
    $joke->delete();

    // Authenticate user
    $user = User::factory()->create();
    $user->assignRole('staff');
    $this->actingAs($user);

    // Recover jokes
    $response = $this->postJson("/api/jokes/trash/recover/{$joke->id}");

    $joke->refresh();

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Joke recovered successfully",
            'data' => $joke->toArray(),
        ]);
});

// Staff level cannot remove deleted jokes
test("staff level cannot remove deleted jokes", function() {
    // Prepare jokes and delete them
    Joke::factory(2)->create();
    Joke::query()->forceDelete();

    // Authenticate user
    $user = User::factory()->create();
    $user->assignRole('staff');
    $this->actingAs($user);

    // Remove jokes
    $response = $this->postJson("/api/jokes/trash/remove-all");

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => [],
        ]);
});

// Admin level and higher can remove deleted jokes
test("admin level and higher can remove deleted jokes", function() {
    // Prepare jokes and delete them
    Joke::factory(2)->create();
    Joke::query()->delete();

    // Authenticate user
    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    // Remove jokes
    $response = $this->postJson("/api/jokes/trash/remove-all");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "2 jokes removed successfully",
        ]);
});

test("admin level and higher can remove one deleted joke", function() {
    $joke = Joke::factory()->create();
    $joke->delete();

    // Authenticate user
    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    // Recover jokes
    $response = $this->postJson("/api/jokes/trash/remove/{$joke->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Joke permanently deleted successfully",
            'data' => '',
        ]);
});

// Banned user cannot access jokes
test('banned user cannot access jokes', function() {
    // Prepare data
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('Password1'),
        'status' => 'banned',
    ]);

    $user->assignRole('client');
    $this->actingAs($user);

    $data = [
        'email' => $user['email'],
        'password' => 'Password1',
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/login", $data);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Your account is banned. Please contact an Administrator for further action.",
            'data' => [],
        ]);
});


// Suspended user cannot access jokes
test('suspended users cannot access jokes', function() {
    // Prepare data
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('Password1'),
        'status' => 'suspended',
    ]);

    $user->assignRole('client');
    $this->actingAs($user);

    $data = [
        'email' => $user['email'],
        'password' => 'Password1',
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/login", $data);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Your account is suspended. Please verify your email address and change your password.",
            'data' => [],
        ]);
});

