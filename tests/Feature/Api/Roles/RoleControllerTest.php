<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Spatie\Permission\Models\Role;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

// Before each test, run this function.
beforeEach(function() {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $this->actingAs($this->user);
});

// Browse all roles
test('staff level or lower cannot browse all roles', function () {
    // Assign role
    $this->user->assignRole('staff');

    // Get all roles
    $response = $this->getJson('/api/admin/roles');

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => [],
        ]);
});

// Browse all roles
test('admin level or higher can browse all roles', function () {
    // Assign role
    $this->user->assignRole('admin');

    // Get all roles
    $response = $this->getJson('/api/admin/roles');

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
        $json->hasAll(['success', 'message', 'data'])
            ->where('success', true)
            ->where('data.current_page', 1)
            ->where('data.per_page', 5)
            ->has('data.data', 4)
        );
});

// Search role
test('admin level or higher can search for a role based on name', function () {
    // Assign role
    $this->user->assignRole('admin');

    // Create roles
    $roles = [
        [
            'name' => 'role 1',
            'level' => 10,
        ],
        [
            'name' => 'role 2',
            'content' => 'role 2 content',
            'level' => 20,
        ],
        [
            'name' => 'role 3',
            'content' => 'role 3 content',
            'level' => 20,
        ],
        [
            'name' => 'This is role 4',
            'content' => 'role 4 content',
            'level' => 30,
        ],
    ];

    foreach($roles as $role) {
        Role::create($role);
    }

    // Get role
    $searchKeyword = 'this';
    $response = $this->getJson("/api/admin/roles?search=$searchKeyword");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
        $json->hasAll(['success', 'message', 'data'])
            ->where('success', true)
            ->where('data.data.0.name', 'This is role 4')
            ->has('data.data', 1)
        );
});

// Create new role
test('admin level or higher can create a new role', function() {
    // Assign role
    $this->user->assignRole('admin');

    // Prepare data
    $data = [
        'name' => 'volunteer',
        'level' => 50,
    ];

    // Send POST request
    $response = $this->postJson("/api/admin/roles", $data);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Role created successfully",
            'data' => $data,
        ]);

    $this->assertDatabaseHas('roles', [
        'name' => 'volunteer'
    ]);
});

test('role name must be unique', function() {
    $this->user->assignRole('admin');

    // Create role
    Role::create([
        'name' => 'volunteer',
        'level' => 50,
    ]);

    // Prepare data
    $data = [
        'name' => 'volunteer',
        'level' => 20,
    ];

    // Send POST request
    $response = $this->postJson("/api/admin/roles", $data);

    // Assert
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => "Please fix the validation errors.",
        ]);
});

// Read role
test('admin level or higher can read a single role', function() {
    $this->user->assignRole('admin');

    $role = Role::findOrFail(1);

    // Get role
    $response = $this->getJson("/api/admin/roles/{$role->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Role retrieved successfully",
            'data' => $role->toArray()
        ]);
});

// Update existing role
test('admin level or higher can update an existing role', function() {
    $this->user->assignRole('admin');

    $role = Role::findOrFail(1);

    // Prepare data
    $updatedRole = [
        'name' => 'user',
    ];

    // Send POST request
    $response = $this->putJson("/api/admin/roles/{$role->id}", $updatedRole);

    $role->refresh();

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Role updated successfully",
            'data' => $role->toArray(),
        ]);
});

// Delete existing role
test('only super-admin can delete an existing role', function() {
    $this->user->assignRole('super-admin');
    $role = Role::findOrFail(1);

    // Send DELETE request
    $response = $this->deleteJson("/api/admin/roles/{$role->id}");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Role removed successfully",
            'data' => [],
        ]);

    $this->assertDatabaseMissing('roles', [
        'name' => $role->name
    ]);
});

test('admin cannot delete an existing role', function() {
    $this->user->assignRole('admin');
    $role = Role::findOrFail(1);

    // Send DELETE request
    $response = $this->deleteJson("/api/admin/roles/{$role->id}");

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => [],
        ]);
});
