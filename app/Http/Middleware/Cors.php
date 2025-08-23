<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Untuk semua request, termasuk OPTIONS
        $response->headers->set('Access-Control-Allow-Origin', 'https://islamic-it-school.com');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        // Jika request OPTIONS, langsung return response
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)->withHeaders([
                'Access-Control-Allow-Origin' => 'https://islamic-it-school.com',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token',
                'Access-Control-Allow-Credentials' => 'true',
            ]);
        }
        
        return $response;
    }
}