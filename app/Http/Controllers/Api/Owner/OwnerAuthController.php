<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OwnerAuthController extends Controller
{
    /**
     * Owner login and get Sanctum token with 'owner' guard.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $owner = User::where('email', $request->email)->first();

        if (!$owner || !Hash::check($request->password, $owner->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Create token specifically for owner guard
        $token = $owner->createToken('owner-token', ['role:owner'])->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil sebagai Owner.',
            'data' => [
                'user' => [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                ],
                'token' => $token,
                'type' => 'owner',
            ],
        ]);
    }

    /**
     * Owner logout and revoke token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Get authenticated owner info.
     */
    public function me(Request $request): JsonResponse
    {
        $owner = $request->user();
        
        return response()->json([
            'data' => [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'rooms_count' => $owner->rooms()->count(),
                'active_tenants_count' => $owner->rooms()->withCount(['tenants' => function($q) {
                    $q->where('status', 'active');
                }])->get()->sum('tenants_count'),
            ],
        ]);
    }

    /**
     * Register new owner (for initial setup or admin).
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $owner = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $owner->createToken('owner-token', ['role:owner'])->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'data' => [
                'user' => [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                ],
                'token' => $token,
            ],
        ], 201);
    }
}
