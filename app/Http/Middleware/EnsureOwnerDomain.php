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
        return $next($request); // Temporary bypass for local testing

        $host = $request->getHost();
        $ownerDomain = config('app.owner_domain');
        $apiDomain = parse_url(config('app.url'), PHP_URL_HOST);
        
        // Allow if accessing via API domain (Central API Architecture)
        if ($host === $apiDomain) {
            return $next($request);
        }

        // Check if accessing from owner domain (Legacy/Direct)
        if ($host !== $ownerDomain && !str_contains($host, 'owner-indekost') && !str_starts_with($host, 'owner.')) {
            return response()->json([
                'message' => 'Akses ditolak. Endpoint ini hanya untuk Owner.',
                'current_domain' => $host,
                'expected_domain' => $ownerDomain,
                'api_domain' => $apiDomain,
            ], 403);
        }

        return $next($request);
    }
}
