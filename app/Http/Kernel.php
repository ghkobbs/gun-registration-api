<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     */
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     */
   // app/Http/Kernel.php
		protected $middlewareGroups = [
				'web' => [
						// \App\Http\Middleware\EncryptCookies::class,
						\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
						\Illuminate\Session\Middleware\StartSession::class,
						\Illuminate\View\Middleware\ShareErrorsFromSession::class,
						// \App\Http\Middleware\VerifyCsrfToken::class,
						\Illuminate\Routing\Middleware\SubstituteBindings::class,
				],

				'api' => [
						\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
						'throttle:api',
						\Illuminate\Routing\Middleware\SubstituteBindings::class,
				],
		];

    /**
     * The application's route middleware.
     */
    protected $routeMiddleware = [
        'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];
}