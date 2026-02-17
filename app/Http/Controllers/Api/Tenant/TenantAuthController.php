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
     * Tenant login using WhatsApp number + Password.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'whatsapp_number' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Normalize WhatsApp number (remove non-digits)
        $inputWA = preg_replace('/[^0-9]/', '', $request->whatsapp_number);
        
        // Find tenant by WA number (handling potential format differences)
        // We'll search for exact match or formatted match if needed.
        // For now, let's assume stored numbers are clean or we clean them for search.
        // Best approach: Store clean numbers. But let's try to find one.
        
        $tenant = Tenant::where('whatsapp_number', $inputWA)
            ->orWhere('whatsapp_number', 'LIKE', "%$inputWA")
            ->first();

        if (! $tenant || ! Hash::check($request->password, $tenant->password)) {
            throw ValidationException::withMessages([
                'whatsapp_number' => ['Kombinasi WhatsApp dan Password tidak sesuai.'],
            ]);
        }

        if ($tenant->status !== 'active') {
            throw ValidationException::withMessages([
                'whatsapp_number' => ['Akun tenant tidak aktif.'],
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
                    'room_number' => $tenant->room->room_number ?? 'N/A',
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
