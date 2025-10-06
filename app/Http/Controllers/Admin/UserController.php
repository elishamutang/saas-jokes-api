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
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // Check if user has the appropriate permissions.
        if (!auth()->user()->hasPermissionTo('browse all users')) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        if (auth()->user()->status === 'suspended') {
            return ApiResponse::error([], "Please reset your password.", 400);
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
    public function store(StoreUserRequest $request): JsonResponse
    {
        // Validate request and whether user is authorized to perform this action.
        $validated = $request->validated();

        // Get role from request
        $role = Role::where('name', $validated['role'])->first();

        // Create user
        $user = User::create($validated);

        // Check appropriate permissions
        if (auth()->user()->hasPermissionTo('create client and staff users only') && $role->name === 'admin') {
            return ApiResponse::error([], "Admins can only assign client or staff roles to users.", 400);
        }

        if (auth()->user()->hasPermissionTo('create client users only') && $role->name !== "client") {
            return ApiResponse::error([], "Staff users can only assign client roles to users.", 400);
        }

        // Assign role
        $user->assignRole($role->name);

        return ApiResponse::success($user, "User created successfully. Please verify the email address.");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        if (!auth()->user()->hasPermissionTo('read any user')) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

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
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        // Validate request and whether user is authorized to perform this action.
        $validated = $request->validated();

        try {
            // Find user and update
            $user = User::findOrFail((int) $id);

            // Get role
            $role = $validated['role'] ?? null;

            // Prevent client users from changing their own role or status.
            if (auth()->user()->hasRole('client')) {
                if (!empty($validated['role'])) {
                    return ApiResponse::error([], "You are not authorized to change your role.", 403);
                }

                if (!empty($validated['status'])) {
                    return ApiResponse::error([], "You are not authorized to change your status.", 403);
                }
            }

            // If admin or higher suspends or bans a user, email_verified_at will become NULL and logout the user.
            if ($validated['status'] !== 'active') {
                // Logout user
                $user->tokens()->delete();
                $user->email_verified_at = null;
            }

            // Update user
            $user->update($validated);

            // Update role
            $user->assignRole($role);

            return ApiResponse::success($user, "User updated successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "User not found", 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        // Get current authenticated user
        $authenticatedUser = auth()->user();

        // Check for appropriate permissions
        if (!$authenticatedUser->hasAnyPermission([
            'delete client users only',
            'delete client and staff users only',
        ])) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        try {
            // Find user to be deleted
            $user = User::findOrFail((int) $id);

            // Super-admin cannot be deleted
            if ($user->hasRole('super-admin')) {
                return ApiResponse::error([], "You are not authorized to perform this action.", 403);
            }

            // Staff and admins cannot delete themselves
            if ($authenticatedUser->hasAnyRole(['staff', 'admin']) && $authenticatedUser->id === $user->id) {
                return ApiResponse::error([], "You are not allowed to delete yourself.", 400);
            }

            // Staff users cannot delete admins and higher
            if ($authenticatedUser->hasRole('staff') && !$user->hasRole('client')) {
                return ApiResponse::error([], "You are not authorized to perform this action.", 403);
            }

            // Client users cannot delete other users.
            if ($authenticatedUser->hasRole('client') && $authenticatedUser->id !== $user->id) {
                return ApiResponse::error([], "You are not authorized to perform this action.", 403);
            }

            $user->delete();

            return ApiResponse::success('', "User deleted successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "User not found", 404);
        }
    }

    /**
     * Show all soft deleted users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trash(Request $request): JsonResponse
    {
        // Check if current user has permission to access soft-deleted users.
        if (!auth()->user()->hasAnyPermission([
            'browse soft-deleted client users',
            'browse soft-deleted users'
        ])) {
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

        // Admin can browse all soft-deleted users
        if (auth()->user()->hasPermissionTo('browse soft-deleted users')) {
            $users = User::onlyTrashed()->paginate($perPage);
        } else {
            // Staff can only browse soft-deleted client users
            $users = User::whereHas('roles', function ($query) {
                $query->where('name', 'client');
            })
                ->onlyTrashed()
                ->paginate($perPage);
        }

        return ApiResponse::success($users, "Deleted users retrieved successfully");
    }

    /**
     * Recover all soft deleted users from trash
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoverAll(): JsonResponse
    {
        // Check if current user has permission to recover all soft-deleted users.
        if (!auth()->user()->hasAnyPermission([
            'restore soft-deleted client users',
            'restore soft-deleted users'
        ])) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        // Admin can recover all users.
        if (auth()->user()->hasPermissionTo('restore soft-deleted users')) {
            $deletedUsers = User::onlyTrashed()->get();
            $numOfDeletedUsersRecovered = User::onlyTrashed()->restore();
        } else {

            $query = User::whereHas('roles', function ($query) {
                $query->where('name', 'client');
            });

            // Staff can only recover client users.
            $deletedUsers = $query->onlyTrashed()->get();
            $numOfDeletedUsersRecovered = $query->restore();
        }

        return ApiResponse::success($deletedUsers, "$numOfDeletedUsersRecovered users recovered successfully");
    }

    /**
     * Remove all soft deleted users from trash
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeAll(): JsonResponse
    {
        // Check if current user has permission to remove all soft-deleted users.
        if (!auth()->user()->hasAnyPermission([
            'remove soft-deleted client users',
            'remove soft-deleted users'
        ])) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        // Admin can remove all users.
        if (auth()->user()->hasPermissionTo('remove soft-deleted users')) {
            $numOfDeletedUsersRemoved = User::onlyTrashed()->forceDelete();
        } else {
            // Staff can only remove client users.
            $numOfDeletedUsersRemoved = User::whereHas('roles', function ($query) {
                $query->where('name', 'client');
            })
                ->onlyTrashed()
                ->forceDelete();
        }

        return ApiResponse::success('', "$numOfDeletedUsersRemoved users removed successfully");
    }

    /**
     * Recover specified soft deleted user from trash
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoverOne(string $id): JsonResponse
    {
        // Check if current user has permission to recover soft-deleted users.
        if (!auth()->user()->hasAnyPermission([
            'restore soft-deleted client users',
            'restore soft-deleted users'
        ])) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        try {
            // Find soft deleted user
            $user = User::onlyTrashed()->findOrFail((int) $id);

            if (auth()->user()->hasPermissionTo('restore soft-deleted client users') && !$user->hasRole('client')) {
                return ApiResponse::error([], "User not found.", 404);
            }

            $user->restore();

            return ApiResponse::success($user, "User recovered successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "User not found.", 404);
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
        // Check if current user has permission to remove all soft-deleted users.
        if (!auth()->user()->hasAnyPermission([
            'remove soft-deleted client users',
            'remove soft-deleted users'
        ])) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        try {
            // Find soft deleted user
            $user = User::onlyTrashed()->findOrFail((int) $id);

            if (auth()->user()->hasPermissionTo('remove soft-deleted client users') && !$user->hasRole('client')) {
                return ApiResponse::error([], "User not found.", 404);
            }

            $user->forceDelete();

            return ApiResponse::success('', "User permanently deleted successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "User not found.", 404);
        }
    }
}
