<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        if (auth()->user()->cannot('viewAny', Role::class)) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        $roles = Role::all();
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
        // Get role
        $role = Role::findOrFail((int) $id);

        if (auth()->user()->cannot('view', $role)) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        return ApiResponse::success($role, "Role retrieved successfully");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // TODO
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // TODO
    }
}
