<?php
// app/Http/Middleware/Cors.php (jika perlu custom CORS)

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request)
            ->header('Access-Control-Allow-Origin', 'https://islamic-it-school.com')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }
}

// app/Http/Kernel.php (update api middleware group jika perlu)
/*
protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \App\Http\Middleware\Cors::class, // jika menggunakan custom CORS
    ],
];
*/

// composer.json dependencies yang dibutuhkan
/*
{
    "require": {
        "php": "^8.1",
        "guzzlehttp/guzzle": "^7.2",
        "laravel/framework": "^10.0",
        "laravel/sanctum": "^3.2",
        "laravel/tinker": "^2.8"
    }
}
*/