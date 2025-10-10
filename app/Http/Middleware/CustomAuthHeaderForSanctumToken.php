<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomAuthHeaderForSanctumToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if custom header exists and inject into Authorization
        if ($request->hasHeader('X-Api-Token')) {
            $token = $request->header('X-Api-Token');
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        Log::info($request->header('Authorization'));

        return $next($request);
    }
}
