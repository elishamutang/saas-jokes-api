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
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $this->actingAs($this->user);
});

// Password field cannot be blank
test('password field cannot be blank', function() {
    // Prepare
    $this->user->assignRole('client');

    $data = [
        'password' => "",
        'password_confirmation' => "",
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/reset-password", $data);

    $response->assertStatus(422)
        ->assertJson([
            'message' => "The password field is required."
        ]);
});

test('password must be at least 6 characters long', function() {
    // Prepare
    $this->user->assignRole('client');

    $data = [
        'password' => "pass",
        'password_confirmation' => "pass",
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/reset-password", $data);

    $response->assertStatus(422)
        ->assertJson([
            'message' => "The password field must be at least 6 characters."
        ]);
});

test('password must be a string', function() {
    // Prepare
    $this->user->assignRole('client');

    $data = [
        'password' => 123456,
        'password_confirmation' => 123456,
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/reset-password", $data);

    $response->assertStatus(422)
        ->assertJson([
            'message' => "The password field must be a string."
        ]);
});

test('password must be confirmed', function() {
    // Prepare
    $this->user->assignRole('client');

    $data = [
        'password' => "password1",
        'password_confirmation' => "",
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/reset-password", $data);

    $response->assertStatus(422)
        ->assertJson([
            'message' => "The password field confirmation does not match."
        ]);
});

test('user cannot re-use previous password', function() {
    // Prepare
    $this->user->password = Hash::make('password1');
    $this->user->assignRole('client');

    $data = [
        'password' => 'password1',
        'password_confirmation' => 'password1',
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/reset-password", $data);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => "New password cannot be the same as previous one.",
            'data' => [],
        ]);
});

test('user status change from suspended to active after password reset', function() {
    // Prepare
    $this->user->status = 'suspended';
    $this->user->assignRole('client');

    $data = [
        'password' => 'password1',
        'password_confirmation' => 'password1',
    ];

    // Send POST request
    $response = $this->postJson("/api/auth/reset-password", $data);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Password reset successfully.",
            'data' => $this->user->toArray(),
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'status' => 'active',
    ]);
});
