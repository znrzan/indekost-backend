<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeterRequest;
use App\Http\Requests\UpdateMeterRequest;
use App\Http\Resources\MeterResource;
use App\Models\Meter;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MeterController extends Controller
{
    /**
     * Display a listing of meters for authenticated owner.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $ownerId = $request->user()->id;
        
        $query = Meter::forOwner($ownerId)->with('room');

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by room
        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Filter low balance meters only
        if ($request->boolean('low_only')) {
            $query->lowBalance();
        }

        $meters = $query->latest()->paginate(15);

        return MeterResource::collection($meters);
    }

    /**
     * Store a newly created meter.
     */
    public function store(StoreMeterRequest $request): JsonResponse
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

        // Auto-inject owner_id and updated_by
        $data = $request->validated();
        $data['owner_id'] = $ownerId;
        $data['updated_by'] = $request->user()->name;

        $meter = Meter::create($data);

        return response()->json([
            'message' => 'Meter berhasil ditambahkan.',
            'data' => new MeterResource($meter->load('room')),
        ], 201);
    }

    /**
     * Display the specified meter.
     */
    public function show(Request $request, Meter $meter): JsonResponse|MeterResource
    {
        // Verify meter belongs to authenticated owner
        if ($meter->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Meter tidak ditemukan.',
            ], 404);
        }

        return new MeterResource($meter->load('room'));
    }

    /**
     * Update the specified meter.
     */
    public function update(UpdateMeterRequest $request, Meter $meter): JsonResponse
    {
        // Verify meter belongs to authenticated owner
        if ($meter->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Meter tidak ditemukan.',
            ], 404);
        }

        $data = $request->validated();
        $data['updated_by'] = $request->user()->name;

        $meter->update($data);

        return response()->json([
            'message' => 'Meter berhasil diupdate.',
            'data' => new MeterResource($meter->load('room')),
        ]);
    }

    /**
     * Remove the specified meter.
     */
    public function destroy(Request $request, Meter $meter): JsonResponse
    {
        // Verify meter belongs to authenticated owner
        if ($meter->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Meter tidak ditemukan.',
            ], 404);
        }

        $meter->delete();

        return response()->json([
            'message' => 'Meter berhasil dihapus.',
        ]);
    }
}
