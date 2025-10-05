<?php

use \App\Models\Category;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

// Browse all categories
test('get all categories', function () {
    // Arrange
    $this->seed(RolesAndPermissionsSeeder::class);
    Category::factory(5)->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign client role
    $user->assignRole('client');
    $this->actingAs($user);

    // Act
    $response = $this->getJson("/api/v2/categories");

    // Assert
    $response
        ->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->where('message', 'Categories retrieved successfully')
                ->where('data.current_page', 1)
                ->where('data.per_page', 5)
                ->has('data.data', 5)
        );
});

// Read a single category
test('retrieve one category', function () {
    // Arrange
    $this->seed(RolesAndPermissionsSeeder::class);
    $category = Category::factory()->create();
    $categoryId = $category->id;

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign client role
    $user->assignRole('client');
    $this->actingAs($user);

    $data = [
        'message' => "Category retrieved successfully",
        'success' => true,
        'data' => $category->toArray(),
    ];

    // Act
    $response = $this->getJson("/api/v2/categories/$categoryId");

    // Assert
    $response
        ->assertStatus(200)
        ->assertJson($data)
        ->assertJsonCount(7, 'data');
});

// Validation tests
// Returns error when querying a category that doesn't yet exist.
test('return error on missing category', function () {
    // Arrange
    $this->seed(RolesAndPermissionsSeeder::class);
    Category::factory()->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign client role
    $user->assignRole('client');
    $this->actingAs($user);

    // Mock result
    $data = [
        'success' => false,
        'message' => 'Category not found',
        'data' => [],
    ];

    // Act
    $response = $this->getJson("/api/v2/categories/9999");

    // Assert
    $response
        ->assertStatus(404)
        ->assertJson($data);
});

// Client users cannot create a new category.
test('client users cannot create a new category', function () {
    // Arrange
    $this->seed(RolesAndPermissionsSeeder::class);
    $data = [
        'title' => 'Fake Category',
        'description' => 'Fake Category Description',
    ];

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign client role
    $user->assignRole('client');
    $this->actingAs($user);

    // Act
    $response = $this->postJson("/api/v2/categories", $data);

    // Assert
    $response
        ->assertStatus(403)
        ->assertJson([
            'message' => "This action is unauthorized."
        ]);
});

// Client users cannot update a category
test('client users cannot update a category', function() {
    // Prepare data
    $this->seed(RolesAndPermissionsSeeder::class);
    $category = Category::factory()->create();
    $categoryId = $category->id;

    // Prepare updated data
    $updatedData = [
        'title' => 'Updated category title',
        'description' => 'Update description title',
    ];

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign staff role
    $user->assignRole('client');
    $this->actingAs($user);

    // Update category
    $response = $this->putJson("/api/v2/categories/$categoryId", $updatedData);

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'message' => "This action is unauthorized."
        ]);

});

// Client users cannot delete a category
test('client users cannot delete a category', function() {
    // Prepare data
    $this->seed(RolesAndPermissionsSeeder::class);
    $categories = Category::factory(2)->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign admin role
    $user->assignRole('client');
    $this->actingAs($user);

    // Get category to be deleted
    $category = $categories->first();
    $categoryId = $category->id;

    // Delete category
    $response = $this->deleteJson("/api/v2/categories/$categoryId");

    // Mock result
    $result = [
        'success' => false,
        'message' => "You are not authorized to perform this action.",
        'data' => [],
    ];

    // Assert
    $response->assertStatus(403)
        ->assertJson($result);
});

// Soft-deletes
test('client users cannot see deleted categories', function() {
    // Prepare
    $this->seed(RolesAndPermissionsSeeder::class);
    Category::factory(2)->create();

    // Delete categories
    Category::query()->delete();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign client role
    $user->assignRole('client');
    $this->actingAs($user);

    // Access deleted categories
    $response = $this->getJson("/api/v2/categories/trash");

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => [],
        ]);
});

// Staff level and higher (admin, super-admin) can create a new category.
test('staff level and higher can create a new category', function () {
    // Arrange
    $this->seed(RolesAndPermissionsSeeder::class);
    $data = [
        'title' => 'Fake Category',
        'description' => 'Fake Category Description',
    ];

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign client role
    $user->assignRole('staff');
    $this->actingAs($user);

    $dataResponse = [
        'message' => "Category created successfully",
        'success' => true,
        'data' => $data
    ];

    // Act
    $response = $this->postJson("/api/v2/categories", $data);

    // Assert
    $response
        ->assertStatus(200)
        ->assertJson($dataResponse)
        ->assertJsonCount(5, 'data');
});

// Validation tests
test('staff level and higher cannot create category with empty title and description', function () {
    // Arrange
    $this->seed(RolesAndPermissionsSeeder::class);
    $data = [
        'title' => '',
        'description' => '',
    ];

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign staff role
    $user->assignRole('staff');
    $this->actingAs($user);

    $response = $this->postJson("/api/v2/categories", $data);

    // 422 Unprocessable Entity
    // The server understands the content type of the request and the request syntax is correct,
    // but the server cannot process the contained instructions due to semantic errors in the request data.
    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'title',
            'description',
        ]);
});

// Update a category
test('staff level and higher can update a single category', function() {
    // Prepare data
    $this->seed(RolesAndPermissionsSeeder::class);
    $category = Category::factory()->create();
    $categoryId = $category->id;

    // Prepare updated data
    $updatedData = [
        'title' => 'Updated category title',
        'description' => 'Update description title',
    ];

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign staff role
    $user->assignRole('staff');
    $this->actingAs($user);

    $result = [
        'success' => true,
        'message' => "Category updated successfully",
        'data' => $updatedData,
    ];

    // Update category
    $response = $this->putJson("/api/v2/categories/$categoryId", $updatedData);

    // Assert
    $response->assertStatus(200)
        ->assertJson($result);
});

// Delete a category
test('staff level and higher can delete a category', function() {
    // Prepare data
    $this->seed(RolesAndPermissionsSeeder::class);
    $categories = Category::factory(2)->create();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign admin role
    $user->assignRole('staff');
    $this->actingAs($user);

    // Get category to be deleted
    $category = $categories->first();
    $categoryId = $category->id;

    // Mock result
    $result = [
        'success' => true,
        'message' => "Category deleted successfully",
        'data' => [],
    ];

    // Delete category
    $response = $this->deleteJson("/api/v2/categories/$categoryId");

    // Assert
    $response->assertStatus(200)
        ->assertJson($result);

    // Verify category is no longer in the database
    $this->assertSoftDeleted('categories', ['id' => $categoryId]);
});

test('staff level and higher can see deleted categories', function() {
    // Prepare
    $this->seed(RolesAndPermissionsSeeder::class);
    Category::factory(2)->create();

    // Delete categories
    Category::query()->delete();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign staff role
    $user->assignRole('staff');
    $this->actingAs($user);

    // Access deleted categories
    $response = $this->getJson("/api/v2/categories/trash");

    // Assert
    $response->assertStatus(200);
});

// Staff can recover soft-deleted categories from trash
test('staff can recover categories from trash', function() {
    // Prepare
    $this->seed(RolesAndPermissionsSeeder::class);
    Category::factory(3)->create();

    // Delete categories
    Category::query()->delete();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign staff role
    $user->assignRole('staff');
    $this->actingAs($user);

    // Restore categories from trash
    $response = $this->postJson("/api/v2/categories/trash/recover-all");

    // Assert
    $response->assertStatus(200);
});

// Staff cannot remove categories from trash
test('staff cannot remove categories from trash', function() {
    // Prepare
    $this->seed(RolesAndPermissionsSeeder::class);
    Category::factory(3)->create();

    // Delete categories
    Category::query()->delete();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign staff role
    $user->assignRole('staff');
    $this->actingAs($user);

    // Remove categories from trash
    $response = $this->postJson("/api/v2/categories/trash/remove-all");

    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => "You are not authorized to perform this action.",
            'data' => [],
        ]);
});

// Admin can remove categories from trash
test('admin level and higher can remove categories from trash', function() {
    // Prepare
    $this->seed(RolesAndPermissionsSeeder::class);
    Category::factory(3)->create();

    // Delete categories
    Category::query()->delete();

    // Create authenticated user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Assign admin role
    $user->assignRole('admin');
    $this->actingAs($user);

    // Remove categories from trash
    $response = $this->postJson("/api/v2/categories/trash/remove-all");

    // Assert
    $response->assertStatus(200);
});
