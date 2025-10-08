<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

beforeEach(function() {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// Check email verified
test('confirm user is not verified', function() {
    // Prepare
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);
    $this->actingAs($user);

    // Send GET request
    $response = $this->getJson("/api/auth/email/verify");

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => "Please verify your email.",
            'data' => []
        ]);
});

test('confirm verified user is verified', function() {
    // Prepare
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    // Send GET request
    $response = $this->getJson("/api/auth/email/verify");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "User email already verified.",
            'data' => $user->toArray(),
        ]);
});

test('unverified users cannot access own profile', function() {
    // Prepare
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);
    $this->actingAs($user);

    // Send GET request
    $response = $this->getJson("/api/profile");

    $response->assertStatus(403)
        ->assertJson([
            'message' => "Your email address is not verified."
        ]);
});

test('resend email verification link', function() {
    // Prepare
    $user = User::factory()->create([
        'email_verified_at' => null,
        'status' => 'active',
    ]);
    $this->actingAs($user);

    // Create custom token
    $token = $user->createToken('testing')->plainTextToken;

    // Send GET request
    $response = $this->withToken($token)->postJson("/api/auth/email/verification-notification");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Verification link sent!",
            'data' => [],
        ]);
});

test('does not re-send email verification link if already verified', function() {
    // Prepare
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $this->actingAs($user);

    // Send GET request
    $response = $this->postJson("/api/auth/email/verification-notification");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "User email already verified.",
            'data' => [],
        ]);
});
