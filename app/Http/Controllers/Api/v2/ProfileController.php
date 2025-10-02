<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        // Validate request
        $validated = $request->validated();

        // Get authenticated user
        $user = $request->user();

        // Update user
        $user->update($validated);
        return ApiResponse::success($user, "Profile updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request): JsonResponse
    {
        // Delete user
        $user = $request->user();
        $user->delete();

        // Automatically logs out the user by removing their tokens.
        $user->tokens()->delete();

        return ApiResponse::success([], "Profile deleted successfully");
    }
}
