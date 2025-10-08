<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Responses\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        if (auth()->user()->cannot('viewAny', Role::class)) {
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
            // Eagerly load Roles.
            $roles = Role::whereAny(['name'], 'LIKE', "%$search%")
                ->paginate($perPage);
        } else {
            // Get all jokes + its categories and votes.
            $roles = Role::paginate($perPage);
        }

        return ApiResponse::success($roles, "Roles retrieved successfully");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Authorise user
        if (auth()->user()->cannot('create', Role::class)) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        // Validate request
        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['required', 'string', 'unique:roles', 'max:255'],
                'level' => ['required', 'integer', 'min:1', 'max:999']
            ]
        );

        if ($validator->fails()) {
            return ApiResponse::error([
                'error' => $validator->errors()
            ], "Please fix the validation errors.", 400);
        }

        // Get validated request and create new role
        $validated = $validator->validated();
        $role = Role::create($validated);

        return ApiResponse::success($role, "Role created successfully");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Get role
            $role = Role::findOrFail((int) $id);

            if (auth()->user()->cannot('view', $role)) {
                return ApiResponse::error([], "You are not authorized to perform this action.", 403);
            }

            return ApiResponse::success($role, "Role retrieved successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Role not found", 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Find role
            $role = Role::findOrFail((int) $id);

            // Authorise user
            if (auth()->user()->cannot('update', $role)) {
                return ApiResponse::error([], "You are not authorized to perform this action.", 403);
            }

            // Validate request
            $validator = Validator::make(
                $request->all(),
                [
                    'name'  => ['sometimes', 'required', 'string', Rule::unique('roles')->ignore((int) $id), 'max:255'],
                    'level' => ['sometimes', 'required', 'integer', 'min:1', 'max:999'],
                ],
            );

            if ($validator->fails()) {
                return ApiResponse::error([
                    'error' => $validator->errors(),
                ], "Please fix validation errors." , 400);
            }

            // Get validated request and update role
            $validated = $validator->validated();
            $role->update($validated);

            return ApiResponse::success($role, "Role updated successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Role not found", 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // Find role
            $role = Role::findOrFail((int) $id);

            // Authorise user
            if (auth()->user()->cannot('destroy', $role)) {
                return ApiResponse::error([], "You are not authorized to perform this action.", 403);
            }

            $role->delete();
            return ApiResponse::success($role, "Role removed successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Role not found", 404);
        }
    }
}
