<?php

use App\Http\Requests\UpdateJokeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

test('validates the joke with valid data', function () {
    // Get instance of UpdateJokeRequest
    $request = new UpdateJokeRequest();

    // Prepare data
    $data = [
        'title' => 'Updated title',
        'content' => 'Updated content',
    ];

    $validator = Validator::make($data, $request->rules());

    // Assert
    expect($validator->passes())->toBeTrue();
});

test('validates the update joke request with empty title field', function () {
    // Get instance of UpdateJokeRequest
    $request = new UpdateJokeRequest();

    // Prepare data
    $data = [
        'title' => '',
        'content' => 'Updated content',
    ];

    $validator = Validator::make($data, $request->rules());

    // Assert
    expect($validator->passes())->toBeTrue();
});

test('validates the update joke request with missing title field', function () {
    // Get instance of UpdateJokeRequest
    $request = new UpdateJokeRequest();

    // Prepare data
    $data = [
        'content' => 'Updated content',
    ];

    $validator = Validator::make($data, $request->rules());

    // Assert
    expect($validator->passes())->toBeTrue();
});

test('fails validation for update joke request with title exceeding max length', function () {
    // Get instance of UpdateJokeRequest
    $request = new UpdateJokeRequest();

    // Prepare data
    $data = [
        'title' => 'Learning to code effectively requires patience, practice, and persistence—keep pushing forward daily!',
        'content' => 'Updated content',
    ];

    $validator = Validator::make($data, $request->rules());

    // Assert
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});
