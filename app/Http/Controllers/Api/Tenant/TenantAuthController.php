<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TenantAuthController extends Controller
{
    /**
     * Tenant login using WhatsApp number + simple PIN/identifier.
     * 
     * For simplicity, we'll use tenant_id + whatsapp_number as credentials.
     * In production, you might want to add a PIN field or use OTP.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'whatsapp_number' => ['required', 'string'],
        ]);

        $tenant = Tenant::find($request->tenant_id);

        // Verify whatsapp number matches
        $inputWA = preg_replace('/[^0-9]/', '', $request->whatsapp_number);
        $tenantWA = preg_replace('/[^0-9]/', '', $tenant->whatsapp_number);

        if ($inputWA !== $tenantWA) {
            throw ValidationException::withMessages([
                'whatsapp_number' => ['Nomor WhatsApp tidak sesuai.'],
            ]);
        }

        // Create token for tenant
        $token = $tenant->createToken('tenant-token', ['role:tenant'])->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'data' => [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'room_number' => $tenant->room->room_number,
                ],
                'token' => $token,
                'type' => 'tenant',
            ],
        ]);
    }

    /**
     * Tenant logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Get authenticated tenant info.
     */
    public function me(Request $request): JsonResponse
    {
        $tenant = $request->user()->load(['room', 'payments']);

        return response()->json([
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'room_number' => $tenant->room->room_number,
                'entry_date' => $tenant->entry_date->toDateString(),
                'monthly_rent' => (float) $tenant->room->price,
                'payments' => $tenant->payments->map(function($payment) {
                    return [
                        'period' => $payment->period,
                        'amount' => (float) $payment->amount,
                        'status' => $payment->status,
                        'payment_date' => $payment->payment_date?->toDateString(),
                    ];
                }),
            ],
        ]);
    }
}
