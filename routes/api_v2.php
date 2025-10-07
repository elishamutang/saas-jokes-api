<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\v2\AuthController as AuthControllerV2;
use App\Http\Controllers\Api\v2\CategoryController as CategoryControllerV2;
use App\Http\Controllers\Api\v2\JokeController as JokeControllerV2;
use App\Http\Controllers\Api\v2\ProfileController as ProfileControllerV2;
use App\Http\Controllers\Api\v2\VerifyEmailController as VerifyEmailControllerV2;
use App\Http\Controllers\Api\v2\VoteController as VoteControllerV2;
use App\Http\Controllers\Api\v2\PasswordResetController as PasswordResetControllerV2;
use App\Http\Controllers\Api\v2\RoleController as RoleControllerV2;
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

        // Reset password
        Route::post('/reset-password', [PasswordResetControllerV2::class, 'resetPassword'])
            ->middleware(['auth:sanctum', 'verified'])->name('reset.password');
    });

// Routes for authenticated and verified users
Route::middleware(['auth:sanctum', 'verified', 'user.status'])->group(function() {
    // Get own user profile
    Route::get('/profile', [AuthControllerV2::class, 'profile'])->name('get.profile');
    Route::put('/profile', [ProfileControllerV2::class, 'update'])->name('update.profile');
    Route::delete('/profile/delete', [ProfileControllerV2::class, 'destroy'])->name('delete.profile');

    // User Admin routes
    Route::prefix('admin/users/trash')->group(function() {
        Route::post('/recover/{id}', [UserController::class, 'recoverOne'])->name('users.recoverOne');
        Route::post('/remove/{id}', [UserController::class, 'removeOne'])->name('users.removeOne');
        Route::post('/recover-all', [UserController::class, 'recoverAll'])->name('users.recoverAll');
        Route::post('/remove-all', [UserController::class, 'removeAll'])->name('users.removeAll');
        Route::get('/', [UserController::class, 'trash'])->name('users.trash');
    });
    Route::apiResource('/admin/users', UserController::class);

    // Role routes
    Route::apiResource("/admin/roles", RoleControllerV2::class);

    // Jokes Routes
    Route::post('/jokes/{id}/like', [VoteControllerV2::class, 'like'])->name('jokes.like');
    Route::post('/jokes/{id}/dislike', [VoteControllerV2::class, 'dislike'])->name('jokes.dislike');
    Route::post('/jokes/{id}/remove-vote', [VoteControllerV2::class, 'removeVote'])->name('vote.remove');

    Route::prefix('jokes/trash')->group(function() {
        Route::post('/recover/{id}', [JokeControllerV2::class, 'recoverOne'])->name('jokes.recoverOne');
        Route::post('/remove/{id}', [JokeControllerV2::class, 'removeOne'])->name('jokes.removeOne');
        Route::post('/recover-all', [JokeControllerV2::class, 'recoverAll'])->name('jokes.recoverAll');
        Route::post('/remove-all', [JokeControllerV2::class, 'removeAll'])->name('jokes.removeAll');
        Route::get('/', [JokeControllerV2::class, 'trash'])->name('jokes.trash');
    });
    Route::apiResource('/jokes', JokeControllerV2::class);

    // Category routes
    Route::prefix('categories/trash')->group(function () {
        Route::post('/recover/{id}', [CategoryControllerV2::class, 'recoverOne'])->name('categories.recoverOne');
        Route::post('/remove/{id}', [CategoryControllerV2::class, 'removeOne'])->name('categories.removeOne');
        Route::post('/recover-all', [CategoryControllerV2::class, 'recoverAll'])->name('categories.recoverAll');
        Route::post('/remove-all', [CategoryControllerV2::class, 'removeAll'])->name('categories.removeAll');
        Route::get('/', [CategoryControllerV2::class, 'trash'])->name('categories.trash');
    });
    Route::apiResource("/categories", CategoryControllerV2::class);
});
