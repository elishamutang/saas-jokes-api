<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJokeRequest;
use App\Http\Requests\UpdateJokeRequest;
use App\Models\Joke;
use App\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class JokeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // Get all jokes
        $jokes = Joke::all();
        return ApiResponse::success($jokes, "Jokes retrieved successfully");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJokeRequest $request)
    {
        // Validate request
        $validated = $request->validated();
        return ApiResponse::success($validated, 'Joke created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $joke = Joke::findOrFail((int) $id);
        return ApiResponse::success($joke, 'Joke retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateJokeRequest $request, string $id)
    {
        // Find joke
        $joke = Joke::findOrFail((int) $id);

        // Validate request
        $validated = $request->validated();

        // Update joke
        $joke->update($validated);
        return ApiResponse::success($joke, 'Joke updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find joke
        $joke = Joke::findOrFail((int) $id);
        $joke->delete();

        return ApiResponse::success('', 'Joke deleted successfully', 204);
    }
}
