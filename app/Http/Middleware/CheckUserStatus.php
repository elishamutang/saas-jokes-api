<?php

namespace App\Http\Middleware;

use App\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if ($request->user()->status === 'suspended') {
            return ApiResponse::error([], "Please reset your password.", 400);
        }

        if ($request->user()->status === 'banned') {
            return ApiResponse::error([], "Your account is banned. Please contact an administrator.", 400);
        }

        return $next($request);
    }
}
