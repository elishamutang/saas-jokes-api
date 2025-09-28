<?php

use App\Http\Requests\StoreJokeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);
testTime()->freeze('2025-09-28 16:37:00');

test('validates the request with valid data', function () {
    // Get instance of StoreJokeRequest
    $request = new StoreJokeRequest();

    // Prepare data
    $data = [
        'title' => 'Joke title',
        'content' => 'Joke content',
    ];

    // Validate
    $validator = Validator::make($data, $request->rules());

    // Assert
    expect($validator->passes())->toBeTrue();
});

test('fails validation for store joke request with missing title', function () {
    // Get instance of StoreJokeRequest
    $request = new StoreJokeRequest();

    // Prepare data
    $data = [
        'content' => 'Joke content',
    ];

    // Validate
    $validator = Validator::make($data, $request->rules());

    // Assert
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

test('fails validation for store joke request with title exceeding max length', function () {
    // Get instance of StoreJokeRequest
    $request = new StoreJokeRequest();

    // Prepare data
    $data = [
        'title' => 'Learning to code effectively requires patience, practice, and persistence—keep pushing forward daily!',
        'content' => 'Joke content',
    ];

    // Validate
    $validator = Validator::make($data, $request->rules());

    // Assert
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});
