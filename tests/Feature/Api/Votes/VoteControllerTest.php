<?php

use App\Models\Joke;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

test('authenticated user can like a joke', function () {
    // Create authenticated user
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    // Create joke
    $joke = Joke::factory()->create();
    $jokeId = $joke->id;

    // Send POST request to endpoint.
    $response = $this->postJson("/api/jokes/$jokeId/like");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->where('message', 'Joke liked successfully')
                ->has('data.votes', 1)
        );

    // Check liked joke in votes table
    $this->assertDatabaseHas('votes', [
        'joke_id' => $jokeId,
        'user_id' => $user->id,
        'rating' => 1,
    ]);
});

test('authenticated user cannot like the same joke more than once', function () {
    // Create authenticated user
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    // Create joke and existing vote
    $joke = Joke::factory()->create();
    $joke->votes()->create([
        'user_id' => $user->id,
        'rating' => 1
    ]);

    // Send POST request to endpoint
    $response = $this->postJson("/api/jokes/{$joke->id}/like");

    // Assert
    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'You have already liked this joke.',
            'data' => [],
        ]);
});

test('authenticated user can dislike a joke', function () {
    // Create authenticated user
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    // Create joke
    $joke = Joke::factory()->create();
    $jokeId = $joke->id;

    // Send POST request to endpoint.
    $response = $this->postJson("/api/jokes/$jokeId/dislike");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
        $json->hasAll(['success', 'message', 'data'])
            ->where('success', true)
            ->where('message', 'Joke disliked successfully')
            ->has('data.votes', 1)
        );

    // Check disliked joke in votes table
    $this->assertDatabaseHas('votes', [
        'joke_id' => $jokeId,
        'user_id' => $user->id,
        'rating' => -1,
    ]);
});

test('authenticated user cannot dislike the same joke more than once', function () {
    // Create authenticated user
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    // Create joke and existing vote
    $joke = Joke::factory()->create();
    $joke->votes()->create([
        'user_id' => $user->id,
        'rating' => -1
    ]);

    // Send POST request to endpoint
    $response = $this->postJson("/api/jokes/{$joke->id}/dislike");

    // Assert
    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'You have already disliked this joke.',
            'data' => [],
        ]);
});

test('authenticated user can remove their vote from a joke', function () {
    // Create authenticated user
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    // Create joke and existing vote
    $joke = Joke::factory()->create();
    $joke->votes()->create([
        'user_id' => $user->id,
        'rating' => 1
    ]);

    // Send POST request to endpoint
    $response = $this->postJson("/api/jokes/{$joke->id}/remove-vote");

    // Assert
    $response->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) =>
            $json->hasAll(['success', 'message', 'data'])
                ->where('success', true)
                ->where('message', 'Vote removed successfully')
        );

    // Check vote is removed from votes table in DB
    $this->assertDatabaseMissing('votes', [
        'joke_id' => $joke->id,
        'user_id' => $user->id,
    ]);
});

test('authenticated user cannot remove a non-existent vote from a joke', function () {
    // Create authenticated user
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    // Create joke and existing vote
    $joke = Joke::factory()->create();

    // Send POST request to endpoint
    $response = $this->postJson("/api/jokes/{$joke->id}/remove-vote");

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => "Unable to remove non-existent vote. You have not voted on this joke.",
            'data' => [],
        ]);
});
