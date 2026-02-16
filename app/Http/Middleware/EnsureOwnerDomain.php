<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerDomain
{
    /**
     * Handle an incoming request from owner subdomain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // Check if accessing from owner subdomain
        if (!str_starts_with($host, 'owner.')) {
            return response()->json([
                'message' => 'Akses ditolak. Endpoint ini hanya untuk Owner.',
            ], 403);
        }

        return $next($request);
    }
}
