<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

beforeEach(function() {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// Register
test('unauthenticated user can register', function() {
    // Prepare data
    $data = [
        'name' => 'user',
        'email' => 'user@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1'
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/register", $data);

    // Assert
    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'User successfully created'
        ]);

    $this->assertDatabaseHas('users', [
        'email' => $data['email'],
    ]);
});

// Login
test('registered user can login', function() {
    // Prepare data
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('Password1'),
    ]);

    $data = [
        'email' => $user['email'],
        'password' => 'Password1',
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/login", $data);

    // Assert
    $response->assertStatus(200);
});

// Access user profile
test('registered user can access own profile', function() {
    // Prepare data
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $user->assignRole('client');
    $this->actingAs($user);

    // Mock data
    $data = [
        'user' => $user->toArray(),
    ];

    // Send GET request
    $response = $this->getJson("/api/profile");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User profile request successful',
            'data' => $data
        ]);
});

// Logout
test('authenticated user can logout', function() {
    // Prepare data
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $user->assignRole('client');
    $this->actingAs($user);

    // Send POST request
    $response = $this->postJson("/api/auth/logout");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Logout successful",
            'data' => []
        ]);
});
