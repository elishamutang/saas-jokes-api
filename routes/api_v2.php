<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\v2\AuthController as AuthControllerV2;
use App\Http\Controllers\Api\v2\CategoryController as CategoryControllerV2;
use App\Http\Controllers\Api\v2\JokeController as JokeControllerV2;
use App\Http\Controllers\Api\v2\ProfileController as ProfileControllerV2;
use App\Http\Controllers\Api\v2\VerifyEmailController as VerifyEmailControllerV2;
use Illuminate\Support\Facades\Route;

/**
 * API Version 2 Routes
 */

/**
 * User API Routes
 * - Register, Login (no authentication)
 * - Profile, Logout, User details (authentication required)
 */

// User authentication routes
Route::prefix('auth')
    ->group(function () {
        Route::post('register', [AuthControllerV2::class, 'register']);
        Route::post('login', [AuthControllerV2::class, 'login']);
        Route::post('logout', [AuthControllerV2::class, 'logout'])
            ->middleware(['auth:sanctum', 'verified']);

        // The Email Verification Notice route
        Route::get('/email/verify', [VerifyEmailControllerV2::class, 'checkEmailVerified'])
            ->middleware(['auth:sanctum'])->name('verification.notice');

        // The Email Verification Handler
        Route::get('/email/verify/{id}/{hash}', [VerifyEmailControllerV2::class, 'verifyEmailHandler'])
            ->middleware(['auth:sanctum', 'signed'])->name('verification.verify');

        // Resending the verification email
        Route::post('/email/verification-notification', [VerifyEmailControllerV2::class, 'resendEmailVerification'])
            ->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
    });

// Routes for authenticated and verified users
Route::middleware(['auth:sanctum', 'verified'])->group(function() {
    // Get own user profile
    Route::get('/profile', [AuthControllerV2::class, 'profile']);
    Route::put('/profile', [ProfileControllerV2::class, 'update']);
    Route::delete('/profile/delete', [ProfileControllerV2::class, 'destroy']);

    // Users routes
    Route::apiResource('/users', UserController::class);

    // Jokes Routes
    Route::apiResource('/jokes', JokeControllerV2::class);

    // Categories routes
    Route::apiResource("/categories", CategoryControllerV2::class);
});

// TODO: Complete other routes below
Route::get('categories/trash', [CategoryControllerV2::class, 'trash'])
    ->name('categories.trash');

Route::delete('categories/trash/empty', [CategoryControllerV2::class, 'removeAll'])
    ->name('categories.trash.remove.all');

Route::post('categories/trash/recover', [CategoryControllerV2::class, 'recoverAll'])
    ->name('categories.trash.recover.all');

Route::delete('categories/trash/{id}/remove', [CategoryControllerV2::class, 'removeOne'])
    ->name('categories.trash.remove.one');

Route::post('categories/trash/{id}/recover', [CategoryControllerV2::class, 'recoverOne'])
    ->name('categories.trash.recover.one');

/** Stop people trying to "GET" admin/categories/trash/1234/delete or similar */
Route::get('categories/trash/{id}/{method}', [CategoryControllerV2::class, 'trash']);

Route::post('categories/{category}/delete', [CategoryControllerV2::class, 'delete'])
    ->name('categories.delete');

Route::get('categories/{category}/delete', function () {
    return redirect()->route('admin.categories.index');
});
