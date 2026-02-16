<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantTicketController extends Controller
{
    /**
     * Display tickets for authenticated tenant.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = $request->user(); // Authenticated tenant
        
        $query = Ticket::where('tenant_id', $tenant->id)->with('room');

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        $tickets = $query->latest()->paginate(15);

        return TicketResource::collection($tickets);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $tenant = $request->user();

        // Upload photo to MinIO if provided
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'tickets/' . uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('', $filename, 's3');
        }

        // Auto-inject tenant_id, room_id, and owner_id
        $ticket = Ticket::create([
            'tenant_id' => $tenant->id,
            'room_id' => $tenant->room_id,
            'owner_id' => $tenant->room->owner_id,
            'title' => $request->title,
            'description' => $request->description,
            'photo_path' => $photoPath,
            'priority' => $request->priority ?? 'medium',
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Laporan kerusakan berhasil dikirim. Owner akan segera menindaklanjuti.',
            'data' => new TicketResource($ticket->load('room')),
        ], 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, Ticket $ticket): JsonResponse|TicketResource
    {
        // Verify ticket belongs to authenticated tenant
        if ($ticket->tenant_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ticket tidak ditemukan.',
            ], 404);
        }

        return new TicketResource($ticket->load('room'));
    }
}
