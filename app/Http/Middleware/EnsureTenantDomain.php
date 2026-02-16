<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantDomain
{
    /**
     * Handle an incoming request from tenant domain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // Reject if trying to access from owner subdomain
        if (str_starts_with($host, 'owner.')) {
            return response()->json([
                'message' => 'Akses ditolak. Endpoint ini untuk Tenant/Public.',
            ], 403);
        }

        return $next($request);
    }
}
