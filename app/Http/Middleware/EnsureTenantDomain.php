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
        $apiDomain = parse_url(config('app.url'), PHP_URL_HOST);

        // Allow if accessing via API domain (Central API Architecture)
        if ($host === $apiDomain) {
            return $next($request);
        }
        
        // Reject if trying to access from owner or admin subdomain
        if (str_contains($host, 'owner-indekost') || str_contains($host, 'admin-indekost') || str_starts_with($host, 'owner.') || str_starts_with($host, 'admin.')) {
            return response()->json([
                'message' => 'Akses ditolak. Endpoint ini untuk Tenant/Public.',
            ], 403);
        }

        return $next($request);
    }
}
