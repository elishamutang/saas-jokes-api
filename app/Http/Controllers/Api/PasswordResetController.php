<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function resetPassword(Request $request): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        // Cannot re-use previous password.
        if (Hash::check($validated['password'], auth()->user()->password)) {
            return ApiResponse::error([], "New password cannot be the same as previous one.", 400);
        }

        // Update user password and set status to active.
        auth()->user()->update([
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        return ApiResponse::success($request->user(), "Password reset successfully.");
    }
}
