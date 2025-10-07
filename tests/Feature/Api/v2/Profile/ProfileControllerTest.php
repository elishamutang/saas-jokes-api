<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');


// Update own password
test("users can update own password", function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare user
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => Hash::make('oldpassword')
    ]);

    $user->assignRole('client');
    $this->actingAs($user);

    // Prepare updated password
    $newPassword = 'newpassword';

    // Update user
    $response = $this->putJson("/api/v2/profile", [
        'password' => $newPassword,
        'password_confirmation' => $newPassword
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Profile updated successfully",
        ]);

    // Reload from database because $user variable still holds old data in memory.
    $user->refresh();

    // Assert password was changed and hashed properly
    assert(Hash::check($newPassword, $user->password));
});

// Delete own profile
test("users can delete their own profile", function() {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Prepare user
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole('client');
    $this->actingAs($user);

    // Delete profile
    $response = $this->deleteJson("/api/v2/profile/delete");

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Profile deleted successfully"
        ]);

    $this->assertSoftDeleted('users', [
        'id' => $user->id
    ]);
});
