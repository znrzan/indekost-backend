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
        $ownerDomain = config('app.owner_domain');
        
        // Check if accessing from owner domain (owner-indekost.ozanqs.my.id)
        if ($host !== $ownerDomain && !str_contains($host, 'owner-indekost') && !str_starts_with($host, 'owner.')) {
            return response()->json([
                'message' => 'Akses ditolak. Endpoint ini hanya untuk Owner.',
            ], 403);
        }

        return $next($request);
    }
}
