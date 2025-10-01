<?php

namespace App\Http\Controllers\Api\v2;

use App\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


/**
 * API Version 2 - AuthController
 */
class AuthController extends Controller
{
    /**
     * Register a User
     *
     * Provide registration capability to the client app
     *
     * Registration requires:
     * - name
     * - valid email address
     * - password (min 6 character)
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users',],
                'password' => ['required', 'string', 'min:6', 'confirmed',],
                'password_confirmation' => ['required', 'string', 'min:6',],
            ]
        );

        if ($validator->fails()) {
            return ApiResponse::error(
                ['error' => $validator->errors()],
                'Registration failed.',
                401
            );
        }

        $user = User::create([
            'name' => $validator->validated()['name'],
            'email' => $validator->validated()['email'],
            'password' => Hash::make(
                $validator->validated()['password']
            ),
        ]);

        // Dispatch successful user registration event to allow Laravel to send verification email.
        event(new Registered($user));

        $token = $user->createToken($user->name)->plainTextToken;

        return ApiResponse::success(
            [
                'token' => $token,
                'user' => $user,
            ],
            'User successfully created',
            201
        );
    }

    /**
     * User Login
     *
     * Attempt to log the user in using email
     * and password based authentication.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users'],
            'password' => ['required', 'string',],
        ]);

        // Validation check
        if ($validator->fails()) {
            return ApiResponse::error(
                [
                    'error' => $validator->errors()
                ],
                'Invalid credentials',
                401
            );
        }

        // Checks if credentials are correct and matches a user in the DB.
        if (!Auth::attempt($request->only('email', 'password'))) {
            return ApiResponse::error(
                [],
                'Invalid credentials',
                401);
        }

        // Get authenticated user and generate API token.
        $user = Auth::user();
        $token = $user->createToken($user->name)->plainTextToken;

        return ApiResponse::success(
            [
                'token' => $token,
                'user' => $user,
            ],
            'Login successful'
        );
    }

    /**
     * User Profile API
     *
     * Provide the user's profile information, including:
     * - name,
     * - email,
     * - email verified,
     * - status,
     * - created at, and
     * - updated at.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        return ApiResponse::success(
            [
                'user' => $request->user(),
            ],
            'User profile request successful'
        );
    }

    /**
     * User Logout
     *
     * Log user out of system, cleaning token and session details.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return ApiResponse::success(
            [],
            'Logout successful'
        );
    }

}
