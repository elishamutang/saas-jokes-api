<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJokeRequest;
use App\Http\Requests\UpdateJokeRequest;
use App\Models\Joke;
use App\Responses\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JokeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // Check for per_page query.
        $perPage = (int) $request->query('per_page', 5);

        if ($perPage < 1) {
            return ApiResponse::error(
                [],
                'Per page must be an integer greater than 0.',
                400,
            );
        }

        // Search
        $validated = $request->validate([
            'search' => ['nullable', 'string'],
        ]);

        $search = $validated['search'] ?? '';
        if (!empty($search)) {
            // Eagerly load Jokes with its categories and votes.
            $jokes = Joke::with(['categories', 'votes'])
                ->whereAny(['title'], 'LIKE', "%$search%")
                ->paginate($perPage);
        } else {
            // Get all jokes + its categories and votes.
            $jokes = Joke::with(['categories', 'votes'])->paginate($perPage);
        }

        return ApiResponse::success($jokes, "Jokes retrieved successfully");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJokeRequest $request): JsonResponse
    {
        // Validate request
        $validated = $request->validated();

        // Create new joke
        $joke = $request->user()->jokes()->create($validated);
        return ApiResponse::success($joke, 'Joke created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $joke = Joke::findOrFail((int) $id);
            return ApiResponse::success($joke, 'Joke retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], 'Joke not found', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateJokeRequest $request, string $id): JsonResponse
    {
        try {
            $joke = Joke::findOrFail((int) $id);

            // Validate request
            $validated = $request->validated();

            // Update joke
            $joke->update($validated);
            return ApiResponse::success($joke, 'Joke updated successfully');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], 'Joke not found', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // Find joke
            $joke = Joke::findOrFail((int) $id);
            $joke->delete();

            return ApiResponse::success([], 'Joke deleted successfully');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], 'Joke not found', 404);
        }
    }
}
