<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request): JsonResponse
    {
        // Get authenticated user
        $user = $request->user();

        // Validate request
        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['sometimes', 'required', 'string'],
                'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user)],
                'password' => ['sometimes', 'required', Password::min(6), 'confirmed'],
            ]
        );

        if ($validator->fails()) {
            return ApiResponse::error([
                'errors' => $validator->errors()
            ], "Please fix the validation errors.", 400);
        }

        // Update user
        $user->update($validator->validated());
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
