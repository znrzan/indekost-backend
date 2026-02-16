<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'proof_of_payment' => $this->proof_url, // Uses accessor from model
            'proof_filename' => $this->proof_filename, // Uses accessor from model
            'payment_date' => $this->payment_date?->toDateString(),
            'period' => $this->period,
            'status' => $this->status,
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
