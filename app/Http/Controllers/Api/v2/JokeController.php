<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJokeRequest;
use App\Http\Requests\UpdateJokeRequest;
use App\Models\Category;
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
        // Check if user has the appropriate permissions.
        if (!auth()->user()->hasAllPermissions(['browse all jokes', 'search a joke'])) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

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
        // Validate request and whether user is authorized to perform this action.
        $validated = $request->validated();

        // Create new joke
        $joke = $request->user()->jokes()->create($validated);

        // Check if categories are present in the request
        $categories = $validated['categories'] ?? [];

        // Get ID for "Unknown" category
        $unknownCategory = Category::where('title', 'Unknown')->first();

        // If $categories are empty, set the joke category to "Unknown".
        // Else, find the category from the DB and attach to joke.
        if (empty($categories)) {
            $joke->categories()->attach($unknownCategory);
        } else {
            // Assuming "categories" is a string like "pirate, maths, server".
            $categoriesStr = str_replace(' ', '', $categories);

            $categoriesArr = array_map(function($category) {
                return ucfirst($category);
            }, explode(',', $categoriesStr));

            // Get corresponding category IDs.
            $categoryIds = Category::whereIn('title', $categoriesArr)->pluck('id')->toArray();

            // If none of the categories correspond to the ones in the DB, then set to Unknown.
            if (empty($categoryIds)) {
                $joke->categories()->attach($unknownCategory);
            } else {
                $joke->categories()->attach($categoryIds);
            }
        }

        return ApiResponse::success($joke, 'Joke created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        // Check if user has the appropriate permissions.
        if (!auth()->user()->hasPermissionTo('read any joke')) {
            return ApiResponse::error([], 'You are not authorized to perform this action.', 403);
        }

        try {
            $joke = Joke::with(['categories', 'votes'])->findOrFail((int) $id);

            $isCategoryEmpty = $joke->categories()->get()->isEmpty();
            $isCategoryUnknown = $joke->categories()->where('title', 'Unknown')->exists();
            $user = auth()->user();

            // Clients cannot see jokes with "Unknown" or empty category.
            if (($isCategoryEmpty || $isCategoryUnknown) && $user->hasRole('client')) {
                return ApiResponse::error([], 'Joke not found', 404);
            }

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

            // Validate request and whether user is authorized to perform this action.
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
        // Get current authenticated user
        $user = auth()->user();

        // Check if current user has permission to delete a joke.
        if (!$user->hasAnyPermission(['delete any joke', 'delete own joke'])) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        try {
            // Find joke
            $joke = Joke::findOrFail((int) $id);

            // Check if user can delete other user's jokes.
            if ($user->id !== $joke->user_id && !$user->hasPermissionTo('delete any joke')) {
                return ApiResponse::error([], "You are not authorized to perform this action.", 403);
            }

            // Delete joke
            $joke->delete();

            return ApiResponse::success([], 'Joke deleted successfully');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], 'Joke not found', 404);
        }
    }

    /**
     * Show all soft deleted jokes
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trash(Request $request): JsonResponse
    {
        // Check if current user has permission to access soft-deleted jokes.
        if (!auth()->user()->hasPermissionTo('browse soft-deleted jokes')) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        // Check if per_page query is present in request.
        $perPage = (int) $request->query('per_page', 5);

        if ($perPage < 1) {
            return ApiResponse::error(
                [],
                'Per page must be more than 0.',
                400
            );
        }

        $jokes = Joke::onlyTrashed()->paginate($perPage);
        return ApiResponse::success($jokes, "Deleted jokes retrieved successfully");
    }

    /**
     * Recover all soft deleted jokes from trash
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoverAll(): JsonResponse
    {
        // Check if current user has permission to recover all soft-deleted jokes.
        if (!auth()->user()->hasPermissionTo('restore soft-deleted jokes')) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        $deletedJokes = Joke::onlyTrashed()->get();
        $numOfJokesRecovered = Joke::onlyTrashed()->restore();

        return ApiResponse::success($deletedJokes, "$numOfJokesRecovered jokes recovered successfully");
    }

    /**
     * Remove all soft deleted jokes from trash
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeAll(): JsonResponse
    {
        // Check if current user has permission to remove all soft-deleted jokes.
        if (!auth()->user()->hasPermissionTo('remove soft-deleted jokes')) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        $numOfJokesRemoved = Joke::onlyTrashed()->forceDelete();
        return ApiResponse::success('', "$numOfJokesRemoved jokes removed successfully");
    }

    /**
     * Recover specified soft deleted joke from trash
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoverOne(string $id): JsonResponse
    {
        // Check if current user has permission to recover soft-deleted jokes.
        if (!auth()->user()->hasPermissionTo('restore soft-deleted jokes')) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        try {
            // Find soft deleted joke
            $joke = Joke::onlyTrashed()->findOrFail((int) $id);
            $joke->restore();

            return ApiResponse::success($joke, "Joke recovered successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Joke not found.", 404);
        }

    }

    /**
     * Remove specified soft deleted category from trash
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeOne(string $id): JsonResponse
    {
        // Check if current user has permission to remove soft-deleted jokes.
        if (!auth()->user()->hasPermissionTo('remove soft-deleted jokes')) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        try {
            // Find soft deleted joke
            $joke = Joke::onlyTrashed()->findOrFail((int) $id);
            $joke->forceDelete();

            return ApiResponse::success('', "Joke permanently deleted successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Joke not found.", 404);
        }
    }
}
