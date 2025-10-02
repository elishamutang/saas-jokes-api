<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Responses\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
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
            $users = User::with(['jokes', 'votes'])->whereAny(
                ['name', 'email'], 'LIKE', "%$search%")
                ->paginate($perPage);
        } else {
            // Get all jokes
            $users = User::with(['jokes', 'votes'])->paginate($perPage);
        }

        return ApiResponse::success($users, "Users retrieved successfully");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        // Validate request
        $validated = $request->validated();

        // Create user
        $user = User::create($validated);
        return ApiResponse::success($user, "User created successfully. Please verify the email address.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        // TODO: Normal users cannot search other users

        try {
            $user = User::with(['jokes', 'votes'])->findOrFail((int) $id);
            return ApiResponse::success($user, "User retrieved successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "User not found", 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        // Validate request
        $validated = $request->validated();

        try {
            // Find user and update
            $user = User::findOrFail((int) $id);
            $user->update($validated);

            return ApiResponse::success($user, "User updated successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "User not found", 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Find user
            $user = User::findOrFail((int) $id);
            $user->delete();

            return ApiResponse::success('', "User deleted successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "User not found", 404);
        }
    }
}
