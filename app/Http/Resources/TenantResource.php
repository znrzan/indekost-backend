<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'whatsapp_number' => $this->whatsapp_number,
            'formatted_whatsapp' => $this->formatted_whatsapp,
            'entry_date' => $this->entry_date?->toDateString(),
            'status' => $this->status,
            'room' => new RoomResource($this->whenLoaded('room')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
