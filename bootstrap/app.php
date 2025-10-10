<?php

use App\Http\Middleware\CheckUserStatus;
use App\Http\Middleware\CustomAuthHeaderForSanctumToken;
use App\Models\Joke;
use App\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (){
//            \Illuminate\Support\Facades\Route::middleware('api')
//                ->prefix('api/v2')
//                ->group(base_path('routes/api_v2.php'));

            /* Add further API versions as required */

        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Check if user is suspended
        $middleware->alias([
            'custom.auth.header' => CustomAuthHeaderForSanctumToken::class,
            'user.status' => CheckUserStatus::class,
        ]);

        $middleware->prependToPriorityList(
            before: \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            prepend: CustomAuthHeaderForSanctumToken::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function(ModelNotFoundException $error, Request $request){
            if($request->wantsJson()){
                return response()->json([
                    'error'=>'entry for '.str_replace('App','',$error->getModel()).' not found'
                    ],
                    404
                );
            }
        });

        // Handle unauthenticated user when visiting /api/v2/jokes endpoint
        $exceptions->render(function(AuthenticationException $error, Illuminate\Http\Request $request) {
            if ($request->wantsJson() || $request->is('api/v2/jokes')) {
                Log::info(getallheaders());
                // Render random joke for unauthenticated users or 'guests'.
                $randomJoke = Joke::inRandomOrder()->first();
                return ApiResponse::error($randomJoke, "Please log into your account.", 401);
            }
        });
    })->create();
