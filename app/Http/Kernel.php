<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
protected $middlewareGroups = [
    'web' => [
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        // ... существующие middleware
    ],

    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

protected $routeMiddleware = [
    // ... другие middleware
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
];

}