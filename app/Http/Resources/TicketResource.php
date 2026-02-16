<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'room' => [
                'id' => $this->room->id,
                'room_number' => $this->room->room_number,
            ],
            'title' => $this->title,
            'description' => $this->description,
            'photo_url' => $this->photo_url, // MinIO accessor
            'photo_filename' => $this->photo_filename,
            'status' => $this->status,
            'priority' => $this->priority,
            'created_at' => $this->created_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
