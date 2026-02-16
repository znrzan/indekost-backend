<?php

use App\Http\Controllers\Api\MeterController;
use App\Http\Controllers\Api\Owner\OwnerAuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\Tenant\TenantAuthController;
use App\Http\Controllers\Api\Tenant\TenantMeterController;
use App\Http\Controllers\Api\Tenant\TenantTicketController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - SaaS Multi-Domain with Zero Trust
|--------------------------------------------------------------------------
|
| Owner routes: owner.indekost.my.id
| Tenant routes: indekost.my.id
|
*/

// ============================================================================
// OWNER ROUTES (owner.indekost.my.id)
// ============================================================================

Route::middleware('ensure.owner.domain')->group(function () {
    // Public owner routes
    Route::post('/owner/login', [OwnerAuthController::class, 'login']);
    Route::post('/owner/register', [OwnerAuthController::class, 'register']);

    // Protected owner routes
    Route::middleware('auth:sanctum')->group(function () {
        // Owner auth
        Route::post('/owner/logout', [OwnerAuthController::class, 'logout']);
        Route::get('/owner/me', [OwnerAuthController::class, 'me']);
        
        // Room management (for owner)
        Route::apiResource('rooms', RoomController::class);
        
        // Tenant management (for owner)
        Route::apiResource('tenants', TenantController::class);
        
        // Payment management (for owner)
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);
        Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject']);
        
        // Meter management (for owner)
        Route::apiResource('meters', MeterController::class);
        
        // Ticket management (for owner)
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
        Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy']);
    });
});

// ============================================================================
// TENANT ROUTES (indekost.my.id - Main Domain)
// ============================================================================

Route::middleware('ensure.tenant.domain')->group(function () {
    // Public tenant routes
    Route::post('/tenant/login', [TenantAuthController::class, 'login']);
    
    // Public payment upload (no auth required for ease of use)
    Route::post('/payments/upload-proof', [PaymentController::class, 'uploadProof']);

    // Protected tenant routes (if tenant logs in)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/tenant/logout', [TenantAuthController::class, 'logout']);
        Route::get('/tenant/me', [TenantAuthController::class, 'me']);
        
        // Meter viewing (for tenant)
        Route::get('/tenant/meters', [TenantMeterController::class, 'index']);
        
        // Ticket creation and viewing (for tenant)
        Route::get('/tenant/tickets', [TenantTicketController::class, 'index']);
        Route::post('/tenant/tickets', [TenantTicketController::class, 'store']);
        Route::get('/tenant/tickets/{ticket}', [TenantTicketController::class, 'show']);
    });
});

// ============================================================================
// Development/Testing Routes (No domain restriction)
// ============================================================================

if (app()->environment('local')) {
    Route::get('/health', function () {
        $whatsappStatus = 'offline';
        $whatsappDetails = null;
        
        try {
            $waService = app(\App\Services\WhatsAppService::class);
            $sessionStatus = $waService->getSessionStatus();
            
            if ($sessionStatus) {
                $whatsappStatus = $sessionStatus['status'] === 'WORKING' ? 'online' : 'offline';
                $whatsappDetails = [
                    'session' => $sessionStatus['name'] ?? 'unknown',
                    'status' => $sessionStatus['status'] ?? 'unknown',
                    'ready' => $waService->isSessionReady(),
                ];
            }
        } catch (\Exception $e) {
            $whatsappDetails = [
                'error' => 'Cannot connect to WAHA',
                'message' => $e->getMessage(),
            ];
        }
        
        return response()->json([
            'status' => 'ok',
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'url' => config('app.url'),
            ],
            'domain' => [
                'current' => request()->getHost(),
                'owner_domain' => config('app.owner_domain'),
                'tenant_domain' => config('app.tenant_domain'),
            ],
            'whatsapp' => [
                'status' => $whatsappStatus,
                'details' => $whatsappDetails,
            ],
            'timestamp' => now()->toISOString(),
        ]);
    });
}
