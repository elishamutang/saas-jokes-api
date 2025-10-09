<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Responses\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Display a listing of the Categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check if user has the appropriate permissions.
        if (auth()->user()->cannot('viewAny', Category::class)) {
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

        // Validate search if present in request.
        $validated = $request->validate([
            'search' => ['nullable', 'string'],
        ]);

        $search = $validated['search'] ?? '';

        if (!empty($search)) {
            $categories = Category::whereAny(
                ['title'], 'LIKE', "%$search%")
                ->paginate($perPage);
        } else {
            $categories = Category::paginate($perPage);
        }

        return ApiResponse::success($categories, "Categories retrieved successfully");
    }

    /**
     * Store a newly created Category in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        // Validate request and whether user is authorized to perform this action.
        $validated = $request->validated();
        $category = Category::create($validated);

        return ApiResponse::success($category, 'Category created successfully');
    }

    /**
     * Display the specified Category.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Find category
            $category = Category::with(['jokes' => function ($query) {
                $query->inRandomOrder()->limit(5)->get();
            }])->findOrFail((int) $id);

            // Check if user has permission
            if (auth()->user()->cannot('read any category', $category)) {
                return ApiResponse::error([], "You are not authorized to perform this action.", 403);
            }

            return ApiResponse::success($category, "Category retrieved successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Category not found", 404);
        }

    }

    /**
     * Update the specified Category in storage.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCategoryRequest $request, string $id)
    {
        // Validate request and whether user is authorized to perform this action.
        $validated = $request->validated();

        try {
            // Find category
            $category = Category::findOrFail((int) $id);
            $category->update($validated);

            return ApiResponse::success($category, "Category updated successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Category not found", 404);
        }
    }

    /**
     * Remove the specified Category from storage.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // Find category
            $category = Category::findOrFail((int) $id);

            // Check if current user has permission to delete a category.
            if (auth()->user()->cannot('delete', $category)) {
                return ApiResponse::error([], "You are not authorized to perform this action.", 403);
            }

            $category->delete();
            $category->jokes()->detach();

            return ApiResponse::success([], "Category deleted successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Category not found", 404);
        }
    }

    /**
     * Show all soft deleted Categories
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trash(Request $request): JsonResponse
    {
        // Check if current user has permission to access soft-deleted categories.
        if (auth()->user()->cannot('checkTrash', Category::class)) {
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

        $categories = Category::onlyTrashed()->paginate($perPage);
        return ApiResponse::success($categories, "Deleted categories retrieved successfully");
    }

    /**
     * Recover all soft deleted categories from trash
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoverAll(): JsonResponse
    {
        $deletedCategories = Category::onlyTrashed()->get();

        if (auth()->user()->cannot('restore', Category::class)) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        $numOfCategoriesRestored = Category::onlyTrashed()->restore();

        return ApiResponse::success($deletedCategories, "$numOfCategoriesRestored categories restored successfully");
    }

    /**
     * Remove all soft deleted categories from trash
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeAll(): JsonResponse
    {
        if (auth()->user()->cannot('remove', Category::class)) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        $numOfCategoriesRemoved = Category::onlyTrashed()->forceDelete();
        return ApiResponse::success('', "$numOfCategoriesRemoved categories removed successfully");
    }

    /**
     * Recover specified soft deleted category from trash
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoverOne(string $id): JsonResponse
    {
        if (auth()->user()->cannot('restore', Category::class)) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        try {
            // Find soft deleted category
            $category = Category::onlyTrashed()->findOrFail((int) $id);
            $category->restore();

            return ApiResponse::success($category, "Category restored successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Category not found.", 404);
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
        if (auth()->user()->cannot('remove', Category::class)) {
            return ApiResponse::error([], "You are not authorized to perform this action.", 403);
        }

        try {
            // Find soft deleted category
            $category = Category::onlyTrashed()->findOrFail((int) $id);
            $category->forceDelete();

            return ApiResponse::success('', "Category permanently deleted successfully");
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error([], "Category not found.", 404);
        }
    }
}
