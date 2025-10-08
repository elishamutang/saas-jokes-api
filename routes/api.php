<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\JokeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\VerifyEmailController;
use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

/**
 * API Routes
 */

// User authentication routes
Route::prefix('auth')
    ->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware(['auth:sanctum', 'verified']);

        // The Email Verification Notice route
        Route::get('/email/verify', [VerifyEmailController::class, 'checkEmailVerified'])
            ->middleware(['auth:sanctum'])->name('verification.notice');

        // The Email Verification Handler
        Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verifyEmailHandler'])
            ->middleware(['auth:sanctum', 'signed'])->name('verification.verify');

        // Resending the verification email
        Route::post('/email/verification-notification', [VerifyEmailController::class, 'resendEmailVerification'])
            ->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');

        // Reset password
        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
            ->middleware(['auth:sanctum', 'verified'])->name('reset.password');
    });

// Routes for authenticated and verified users
Route::middleware(['auth:sanctum', 'verified', 'user.status'])->group(function() {
    // Get own user profile
    Route::get('/profile', [AuthController::class, 'profile'])->name('get.profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('update.profile');
    Route::delete('/profile/delete', [ProfileController::class, 'destroy'])->name('delete.profile');

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
    Route::apiResource("/admin/roles", RoleController::class);

    // Jokes Routes
    Route::post('/jokes/{id}/like', [VoteController::class, 'like'])->name('jokes.like');
    Route::post('/jokes/{id}/dislike', [VoteController::class, 'dislike'])->name('jokes.dislike');
    Route::post('/jokes/{id}/remove-vote', [VoteController::class, 'removeVote'])->name('vote.remove');

    Route::prefix('jokes/trash')->group(function() {
        Route::post('/recover/{id}', [JokeController::class, 'recoverOne'])->name('jokes.recoverOne');
        Route::post('/remove/{id}', [JokeController::class, 'removeOne'])->name('jokes.removeOne');
        Route::post('/recover-all', [JokeController::class, 'recoverAll'])->name('jokes.recoverAll');
        Route::post('/remove-all', [JokeController::class, 'removeAll'])->name('jokes.removeAll');
        Route::get('/', [JokeController::class, 'trash'])->name('jokes.trash');
    });
    Route::apiResource('/jokes', JokeController::class);

    // Category routes
    Route::prefix('categories/trash')->group(function () {
        Route::post('/recover/{id}', [CategoryController::class, 'recoverOne'])->name('categories.recoverOne');
        Route::post('/remove/{id}', [CategoryController::class, 'removeOne'])->name('categories.removeOne');
        Route::post('/recover-all', [CategoryController::class, 'recoverAll'])->name('categories.recoverAll');
        Route::post('/remove-all', [CategoryController::class, 'removeAll'])->name('categories.removeAll');
        Route::get('/', [CategoryController::class, 'trash'])->name('categories.trash');
    });
    Route::apiResource("/categories", CategoryController::class);
});
