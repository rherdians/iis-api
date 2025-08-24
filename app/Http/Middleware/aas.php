<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class aas
{
   public function handle(Request $request, Closure $next)
{
    $response = $next($request);

    $allowedOrigins = [
        'https://islamic-it-school.com',
        'https://www.islamic-it-school.com',
    ];

    $origin = $request->headers->get('Origin');

    if (in_array($origin, $allowedOrigins)) {
        $response->headers->set('Access-Control-Allow-Origin', $origin);
    }

    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
    $response->headers->set('Access-Control-Allow-Credentials', 'true');

    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->withHeaders([
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token',
            'Access-Control-Allow-Credentials' => 'true',
        ]);
    }

    return $response;
}

}