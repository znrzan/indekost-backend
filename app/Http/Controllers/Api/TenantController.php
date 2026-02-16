<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $ownerId = $request->user()->id;
        
        // Get tenants only from rooms owned by this owner
        $query = Tenant::whereHas('room', function($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        });

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by room
        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $tenants = $query->with(['room', 'payments'])->paginate(15);

        return TenantResource::collection($tenants);
    }

    /**
     * Store a newly created tenant.
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $ownerId = $request->user()->id;
        
        // Verify room belongs to this owner
        $room = Room::where('id', $request->room_id)
            ->where('owner_id', $ownerId)
            ->first();
            
        if (!$room) {
            return response()->json([
                'message' => 'Kamar tidak ditemukan.',
            ], 404);
        }
        
        $tenant = Tenant::create($request->validated());

        // Update room status to occupied if tenant is active
        if ($tenant->status === 'active') {
            $tenant->room->update(['status' => 'occupied']);
        }

        return response()->json([
            'message' => 'Penyewa berhasil ditambahkan.',
            'data' => new TenantResource($tenant->load('room')),
        ], 201);
    }

    /**
     * Display the specified tenant.
     */
    public function show(Request $request, Tenant $tenant): TenantResource|JsonResponse
    {
        // Verify tenant's room belongs to authenticated owner
        if ($tenant->room->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Penyewa tidak ditemukan.',
            ], 404);
        }
        
        return new TenantResource($tenant->load(['room', 'payments']));
    }

    /**
     * Update the specified tenant.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        // Verify tenant's room belongs to authenticated owner
        if ($tenant->room->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Penyewa tidak ditemukan.',
            ], 404);
        }
        
        // If changing room, verify new room also belongs to this owner
        if ($request->has('room_id') && $request->room_id != $tenant->room_id) {
            $newRoom = Room::where('id', $request->room_id)
                ->where('owner_id', $request->user()->id)
                ->first();
                
            if (!$newRoom) {
                return response()->json([
                    'message' => 'Kamar baru tidak ditemukan.',
                ], 404);
            }
        }
        
        $oldRoom = $tenant->room;
        $oldStatus = $tenant->status;

        $tenant->update($request->validated());

        // If status changed from active to inactive, set room to available
        if ($oldStatus === 'active' && $tenant->status === 'inactive') {
            $oldRoom->update(['status' => 'available']);
        }

        // If status changed from inactive to active, set room to occupied
        if ($oldStatus === 'inactive' && $tenant->status === 'active') {
            $tenant->room->update(['status' => 'occupied']);
        }

        // If room changed, update old and new room status
        if ($request->has('room_id') && $tenant->room_id !== $oldRoom->id) {
            $oldRoom->update(['status' => 'available']);
            if ($tenant->status === 'active') {
                $tenant->room->update(['status' => 'occupied']);
            }
        }

        return response()->json([
            'message' => 'Penyewa berhasil diupdate.',
            'data' => new TenantResource($tenant->load('room')),
        ]);
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Request $request, Tenant $tenant): JsonResponse
    {
        // Verify tenant's room belongs to authenticated owner
        if ($tenant->room->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Penyewa tidak ditemukan.',
            ], 404);
        }
        
        $room = $tenant->room;

        $tenant->delete();

        // Set room to available after tenant is deleted
        $room->update(['status' => 'available']);

        return response()->json([
            'message' => 'Penyewa berhasil dihapus.',
        ]);
    }
}
