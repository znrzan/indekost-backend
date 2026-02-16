<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust proxies for Cloudflare Tunnel / Zero Trust
        $middleware->trustProxies(at: '*');
        
        // Register custom middleware aliases
        $middleware->alias([
            'ensure.owner.domain' => \App\Http\Middleware\EnsureOwnerDomain::class,
            'ensure.tenant.domain' => \App\Http\Middleware\EnsureTenantDomain::class,
        ]);

        // Apply domain validation globally to API routes
        $middleware->api(append: [
            \App\Http\Middleware\EnsureValidDomain::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
