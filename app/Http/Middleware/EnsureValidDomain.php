<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidDomain
{
    /**
     * Handle an incoming request.
     * 
     * Validates that the request domain matches the expected configuration
     * for owner or tenant domains.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentHost = $request->getHost();
        $ownerDomain = config('app.owner_domain');
        $tenantDomain = config('app.tenant_domain');
        $apiDomain = parse_url(config('app.url'), PHP_URL_HOST);

        // 1. Bypass check if accessing via Central API Domain (Zero Trust Architecture)
        if ($currentHost === $apiDomain) {
            return $next($request);
        }

        // 2. Bypass for local testing (optional, remove in production)
        if ($currentHost === 'localhost' || str_contains($currentHost, '127.0.0.1')) {
            return $next($request);
        }

        // Check if accessing owner endpoints from correct domain
        if ($this->isOwnerEndpoint($request)) {
            // Owner endpoints should come from owner domain
            if (!$this->matchesDomain($currentHost, $ownerDomain)) {
                return response()->json([
                    'message' => 'Invalid domain. Owner endpoints hanya dapat diakses dari: ' . $ownerDomain,
                    'current_domain' => $currentHost,
                    'expected_domain' => $ownerDomain,
                ], 403);
            }
        }

        // Check if accessing tenant endpoints from correct domain
        if ($this->isTenantEndpoint($request)) {
            // Tenant endpoints should NOT come from owner subdomain
            if ($this->matchesDomain($currentHost, $ownerDomain)) {
                return response()->json([
                    'message' => 'Invalid domain. Tenant endpoints tidak dapat diakses dari owner subdomain.',
                    'current_domain' => $currentHost,
                    'expected_domain' => $tenantDomain,
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Check if the request is for an owner endpoint.
     */
    protected function isOwnerEndpoint(Request $request): bool
    {
        $path = $request->path();
        
        return str_starts_with($path, 'api/owner/') ||
               str_starts_with($path, 'api/rooms') ||
               str_starts_with($path, 'api/tenants');
    }

    /**
     * Check if the request is for a tenant endpoint.
     */
    protected function isTenantEndpoint(Request $request): bool
    {
        $path = $request->path();
        
        return str_starts_with($path, 'api/tenant/') ||
               str_starts_with($path, 'api/payments/upload-proof');
    }

    /**
     * Check if current host matches expected domain pattern.
     */
    protected function matchesDomain(string $currentHost, string $expectedDomain): bool
    {
        // Exact match
        if ($currentHost === $expectedDomain) {
            return true;
        }

        // Wildcard subdomain match (e.g., *.indekost.test)
        if (str_starts_with($expectedDomain, '*.')) {
            $baseDomain = substr($expectedDomain, 2);
            return str_ends_with($currentHost, $baseDomain);
        }

        // For owner subdomains (e.g., owner.indekost.test)
        if (str_contains($expectedDomain, '.')) {
            return $currentHost === $expectedDomain;
        }

        return false;
    }
}
