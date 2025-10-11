<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Responses\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Email Verification
 *
 * APIs for verifying user email address.
 */
class VerifyEmailController extends Controller
{
    /**
     * Checks whether the current user is verified or not.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkEmailVerified(Request $request): JsonResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? ApiResponse::success($request->user(), "User email already verified.")
            : ApiResponse::error([], "Please verify your email.", 401);
    }

    /**
     * Handles the requests generated when the user clicks the email verification link.
     *
     * @param EmailVerificationRequest $request
     * @return JsonResponse
     */
    // @codeCoverageIgnoreStart
    public function verifyEmailHandler(EmailVerificationRequest $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return ApiResponse::success($request->user(), "User email already verified.");
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // Check for user status
        if ($request->user()->status === 'suspended') {
            return ApiResponse::success([], "Please reset your password.");
        }

        // Set user status as ACTIVE
        $request->user()->update([
            'status' => 'active',
        ]);

        return ApiResponse::success($request->user(), "Email verified successfully.");
    }
    // @codeCoverageIgnoreEnd

    /**
     * Re-sends email verification to user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendEmailVerification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return ApiResponse::success([], "User email already verified.");
        }

        $request->user()->sendEmailVerificationNotification();
        return ApiResponse::success([], "Verification link sent!");
    }
}
