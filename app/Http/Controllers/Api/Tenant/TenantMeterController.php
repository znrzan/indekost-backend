<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeterResource;
use App\Models\Meter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantMeterController extends Controller
{
    /**
     * Display meters for authenticated tenant's room.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = $request->user(); // Authenticated tenant
        
        // Get meters for tenant's room
        $meters = Meter::where('room_id', $tenant->room_id)
            ->with('room')
            ->get();

        return MeterResource::collection($meters);
    }
}
