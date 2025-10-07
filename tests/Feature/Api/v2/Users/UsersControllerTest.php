<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\Fluent\AssertableJson;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

// Client users cannot browse all users
test('client users cannot browse all users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(10)->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign role
    $user->assignRole('client');
    $this->actingAs($user);

    // Get users
    $response = $this->getJson('/api/v2/admin/users');

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => [],
        ]);
});


// Staff level and higher can browse all users
test('staff level and higher can browse all users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(10)->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign role
    $user->assignRole('staff');
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

// Staff level and higher can paginate users
test('staff level and higher can paginate users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(10)->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole('staff');
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

// Staff level and higher can search for users
test('staff level and higher can search for user based on name or email', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

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
        'status' => 'active',
    ]);

    $authUser->assignRole('staff');
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

// Staff level and higher can get a single user
test('staff level and higher can get a single user', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole('staff');
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

// Staff can create client users
test('staff can create client users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare user to be created
    $user = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password1',
        'role' => 'client',
    ];

    // Act as another authenticated user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('staff');
    $this->actingAs($authUser);

    // Create user
    $response = $this->postJson("/api/v2/admin/users", $user);

    // Assert
    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'email' => 'new@example.com'
    ]);
});

// Staff cannot create users with staff or higher level roles
test('staff cannot create staff or higher level users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare user to be created
    $user = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password1',
        'role' => 'staff',
    ];

    // Act as another authenticated user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('staff');
    $this->actingAs($authUser);

    // Create user
    $response = $this->postJson("/api/v2/admin/users", $user);

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "Staff users can only assign client roles to users.",
            'data' => [],
        ]);
});

// Admin can create users with client or staff roles
test('admin can create users with client role', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare user to be created
    $user = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password1',
        'role' => 'client',
    ];

    // Act as another authenticated user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('admin');
    $this->actingAs($authUser);

    // Create user
    $response = $this->postJson("/api/v2/admin/users", $user);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "User created successfully. Please verify the email address.",
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'new@example.com'
    ]);
});

test('admin can create users with staff role', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare user to be created
    $user = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password1',
        'role' => 'staff',
    ];

    // Act as another authenticated user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('admin');
    $this->actingAs($authUser);

    // Create user
    $response = $this->postJson("/api/v2/admin/users", $user);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "User created successfully. Please verify the email address.",
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'new@example.com'
    ]);
});

test('admin cannot create users with admin role or higher', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare user to be created
    $user = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password1',
        'role' => 'admin',
    ];

    // Act as another authenticated user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('admin');
    $this->actingAs($authUser);

    // Create user
    $response = $this->postJson("/api/v2/admin/users", $user);

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "Admins can only assign client or staff roles to users.",
            'data' => [],
        ]);
});

// Clients can update own user name and email
test("clients can update own user name and email", function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $user->assignRole('client');
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
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $userId,
        'name' => $updatedUser['name'],
        'email' => $updatedUser['email'],
    ]);
});

// Staff can update client users' name and email
test("staff can update client user name and email", function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(3)->create();

    // Prepare authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $user->assignRole('staff');
    $this->actingAs($user);

    // Get user id
    $selectedUser = User::first()->assignRole('client');

    // Prepare updated data
    $updatedUser = [
        'name' => 'Updated user',
        'email' => 'updated@example.com',
    ];

    // Update user
    $response = $this->putJson("/api/v2/admin/users/{$selectedUser->id}", $updatedUser);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User updated successfully',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $selectedUser->id,
        'name' => $updatedUser['name'],
        'email' => $updatedUser['email'],
    ]);
});

// Admin can update admin, staff or client users
test("admin can admin, staff or client users", function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(3)->create();

    // Prepare authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $user->assignRole('admin');
    $this->actingAs($user);

    // Get user id
    $selectedUser = User::first()->assignRole('admin');

    // Prepare updated data
    $updatedUser = [
        'name' => 'Updated user',
        'email' => 'updated@example.com',
    ];

    // Update user
    $response = $this->putJson("/api/v2/admin/users/{$selectedUser->id}", $updatedUser);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User updated successfully',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $selectedUser->id,
        'name' => $updatedUser['name'],
        'email' => $updatedUser['email'],
    ]);
});

// Super-admin can update any user
test("super-admin can update any user", function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(3)->create();

    // Prepare authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $user->assignRole('super-admin');
    $this->actingAs($user);

    // Get user id
    $selectedUser = User::first()->assignRole('admin');

    // Prepare updated data
    $updatedUser = [
        'name' => 'Updated user',
        'email' => 'updated@example.com',
    ];

    // Update user
    $response = $this->putJson("/api/v2/admin/users/{$selectedUser->id}", $updatedUser);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User updated successfully',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $selectedUser->id,
        'name' => $updatedUser['name'],
        'email' => $updatedUser['email'],
    ]);
});

// Delete users
test('staff can delete client users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(5)->create();

    // Authenticate user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('staff');
    $this->actingAs($authUser);

    // Get user to be deleted
    $user = User::first();
    $user->assignRole('client');

    // Delete user
    $response = $this->deleteJson("/api/v2/admin/users/{$user->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "User deleted successfully"
        ]);

    $this->assertSoftDeleted('users', [
        'id' => $user->id
    ]);
});

test('staff cannot delete staff or higher level users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(5)->create();

    // Authenticate user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('staff');
    $this->actingAs($authUser);

    // Get user to be deleted
    $user = User::first();
    $user->assignRole('staff');

    // Delete user
    $response = $this->deleteJson("/api/v2/admin/users/{$user->id}");

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => [],
        ]);
});

test('admin can delete staff users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(5)->create();

    // Authenticate user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('admin');
    $this->actingAs($authUser);

    // Get user to be deleted
    $user = User::first();
    $user->assignRole('staff');

    // Delete user
    $response = $this->deleteJson("/api/v2/admin/users/{$user->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "User deleted successfully"
        ]);

    $this->assertSoftDeleted('users', [
        'id' => $user->id
    ]);
});

test('admin cannot delete admin or higher level users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(5)->create();

    // Authenticate user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('admin');
    $this->actingAs($authUser);

    // Get user to be deleted
    $user = User::first();
    $user->assignRole('admin');

    // Delete user
    $response = $this->deleteJson("/api/v2/admin/users/{$user->id}");

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => [],
        ]);
});

test('super-admin can delete any user', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    User::factory(5)->create();

    // Authenticate user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('super-admin');
    $this->actingAs($authUser);

    // Get user to be deleted
    $user = User::first();
    $user->assignRole('admin');

    // Delete user
    $response = $this->deleteJson("/api/v2/admin/users/{$user->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "User deleted successfully"
        ]);

    $this->assertSoftDeleted('users', [
        'id' => $user->id
    ]);
});

test('super-admin cannot be deleted', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Authenticate user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('super-admin');
    $this->actingAs($authUser);

    // Delete user
    $response = $this->deleteJson("/api/v2/admin/users/{$authUser->id}");

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => []
        ]);
});

test('staff can only browse soft-deleted client users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    $users = User::factory(3)->create();

    foreach ($users as $user) {
        $user->assignRole('staff');
        $user->delete();
    }

    $clientUser = User::factory()->create();
    $clientUser->assignRole('client');
    $clientUser->delete();

    // Authenticate user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('staff');
    $this->actingAs($authUser);

    // Browse trash
    $response = $this->getJson("/api/v2/admin/users/trash");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->where('message', 'Deleted users retrieved successfully')
                ->where('data.data.0', $clientUser)
        );
});

test('admin can browse all soft-deleted users', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    $users = User::factory(3)->create();

    foreach ($users as $user) {
        $user->assignRole('staff');
        $user->delete();
    }

    $clientUser = User::factory()->create();
    $clientUser->assignRole('client');
    $clientUser->delete();

    // Authenticate user
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $authUser->assignRole('admin');
    $this->actingAs($authUser);

    // Browse trash
    $response = $this->getJson("/api/v2/admin/users/trash");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['success', 'message', 'data'])
            ->where('success', true)
            ->where('message', 'Deleted users retrieved successfully')
            ->where('data.data', User::onlyTrashed()->get()->toArray())
        );
});

test('staff can recover client user', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    $newUser = User::factory()->create([
        'status' => 'active',
    ]);
    $newUser->assignRole('client');
    $newUser->delete();

    // Authenticate user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $user->assignRole('staff');
    $this->actingAs($user);

    // Recover deleted user
    $response = $this->postJson("/api/v2/admin/users/trash/recover/{$newUser->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User recovered successfully',
        ]);
});

test('admin can recover staff user', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    $newUser = User::factory()->create([
        'status' => 'active',
    ]);
    $newUser->assignRole('staff');
    $newUser->delete();

    // Authenticate user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $user->assignRole('admin');
    $this->actingAs($user);

    // Recover deleted user
    $response = $this->postJson("/api/v2/admin/users/trash/recover/{$newUser->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User recovered successfully',
        ]);
});

test('admin can remove staff user or lower', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    $newUser = User::factory()->create([
        'status' => 'active',
    ]);
    $newUser->assignRole('staff');
    $newUser->delete();

    // Authenticate user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $user->assignRole('admin');
    $this->actingAs($user);

    // Recover deleted user
    $response = $this->postJson("/api/v2/admin/users/trash/remove/{$newUser->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User permanently deleted successfully',
        ]);
});

test('super-admin can recover any user', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    $newUser = User::factory()->create([
        'status' => 'active',
    ]);
    $newUser->assignRole('admin');
    $newUser->delete();

    // Authenticate user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $user->assignRole('super-admin');
    $this->actingAs($user);

    // Recover deleted user
    $response = $this->postJson("/api/v2/admin/users/trash/recover/{$newUser->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User recovered successfully',
        ]);
});

test('super-admin can remove any user', function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare users
    $newUser = User::factory()->create([
        'status' => 'active',
    ]);
    $newUser->assignRole('admin');
    $newUser->delete();

    // Authenticate user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $user->assignRole('super-admin');
    $this->actingAs($user);

    // Recover deleted user
    $response = $this->postJson("/api/v2/admin/users/trash/remove/{$newUser->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User permanently deleted successfully',
        ]);
});

