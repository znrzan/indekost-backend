<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $ownerId = $request->user()->id;
        
        $query = Room::forOwner($ownerId);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by room number
        if ($request->has('search')) {
            $query->where('room_number', 'like', '%' . $request->search . '%');
        }

        $rooms = $query->with('currentTenant')->paginate(15);

        return RoomResource::collection($rooms);
    }

    /**
     * Store a newly created room.
     */
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['owner_id'] = $request->user()->id;
        
        $room = Room::create($data);

        return response()->json([
            'message' => 'Kamar berhasil ditambahkan.',
            'data' => new RoomResource($room),
        ], 201);
    }

    /**
     * Display the specified room.
     */
    public function show(Request $request, Room $room): RoomResource|JsonResponse
    {
        // Verify room belongs to authenticated owner
        if ($room->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Kamar tidak ditemukan.',
            ], 404);
        }
        
        return new RoomResource($room->load('currentTenant'));
    }

    /**
     * Update the specified room.
     */
    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        // Verify room belongs to authenticated owner
        if ($room->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Kamar tidak ditemukan.',
            ], 404);
        }
        
        $room->update($request->validated());

        return response()->json([
            'message' => 'Kamar berhasil diupdate.',
            'data' => new RoomResource($room),
        ]);
    }

    /**
     * Remove the specified room.
     */
    public function destroy(Request $request, Room $room): JsonResponse
    {
        // Verify room belongs to authenticated owner
        if ($room->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Kamar tidak ditemukan.',
            ], 404);
        }
        
        // Check if room has active tenant
        if ($room->currentTenant()->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus kamar yang masih ditempati.',
            ], 422);
        }

        $room->delete();

        return response()->json([
            'message' => 'Kamar berhasil dihapus.',
        ]);
    }
}
