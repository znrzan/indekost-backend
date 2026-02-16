<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets for authenticated owner.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $ownerId = $request->user()->id;
        
        $query = Ticket::forOwner($ownerId)->with(['tenant', 'room']);

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        // Filter by room
        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Filter by tenant
        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $tickets = $query->latest()->paginate(15);

        return TicketResource::collection($tickets);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, Ticket $ticket): JsonResponse|TicketResource
    {
        // Verify ticket belongs to authenticated owner
        if ($ticket->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ticket tidak ditemukan.',
            ], 404);
        }

        return new TicketResource($ticket->load(['tenant', 'room']));
    }

    /**
     * Update ticket status.
     */
    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): JsonResponse
    {
        // Verify ticket belongs to authenticated owner
        if ($ticket->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ticket tidak ditemukan.',
            ], 404);
        }

        $ticket->update([
            'status' => $request->status,
            'resolved_at' => $request->status === 'resolved' ? now() : null,
        ]);

        return response()->json([
            'message' => 'Status ticket berhasil diupdate.',
            'data' => new TicketResource($ticket->load(['tenant', 'room'])),
        ]);
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        // Verify ticket belongs to authenticated owner
        if ($ticket->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ticket tidak ditemukan.',
            ], 404);
        }

        // Delete photo from MinIO if exists
        if ($ticket->photo_path) {
            \Storage::disk('s3')->delete($ticket->photo_path);
        }

        $ticket->delete();

        return response()->json([
            'message' => 'Ticket berhasil dihapus.',
        ]);
    }
}
