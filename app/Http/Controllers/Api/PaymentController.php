<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadPaymentProofRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $ownerId = $request->user()->id;
        
        $query = Payment::forOwner($ownerId);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by tenant
        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Filter by period
        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        $payments = $query->with('tenant.room')->latest()->paginate(15);

        return PaymentResource::collection($payments);
    }

    /**
     * Upload payment proof by tenant.
     */
    public function uploadProof(UploadPaymentProofRequest $request): JsonResponse
    {
        $tenant = Tenant::findOrFail($request->tenant_id);

        // Store the file in S3/MinIO with unique filename
        $file = $request->file('proof');
        $filename = 'payment-proofs/' . uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('', $filename, 's3');

        // Get owner_id dari tenant's room
        $ownerId = $tenant->room->owner_id;

        // Create or update payment record
        $payment = Payment::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'period' => $request->period,
            ],
            [
                'owner_id' => $ownerId,
                'amount' => $tenant->room->price,
                'proof_of_payment' => $path,
                'payment_date' => now(),
                'status' => 'pending',
            ]
        );

        return response()->json([
            'message' => 'Bukti pembayaran berhasil diupload. Menunggu verifikasi.',
            'data' => new PaymentResource($payment->load('tenant')),
        ], 201);
    }

    /**
     * Verify payment.
     */
    public function verify(Request $request, Payment $payment): JsonResponse
    {
        // Verify payment belongs to authenticated owner
        if ($payment->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Payment tidak ditemukan.',
            ], 404);
        }
        
        $payment->update(['status' => 'verified']);

        return response()->json([
            'message' => 'Pembayaran berhasil diverifikasi.',
            'data' => new PaymentResource($payment->load('tenant')),
        ]);
    }

    /**
     * Reject payment.
     */
    public function reject(Request $request, Payment $payment): JsonResponse
    {
        // Verify payment belongs to authenticated owner
        if ($payment->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Payment tidak ditemukan.',
            ], 404);
        }
        
        // Delete old proof from S3 if exists
        if ($payment->proof_of_payment) {
            \Storage::disk('s3')->delete($payment->proof_of_payment);
        }
        
        $payment->update([
            'status' => 'rejected',
            'proof_of_payment' => null,
        ]);

        return response()->json([
            'message' => 'Pembayaran ditolak. Penyewa harus upload ulang bukti pembayaran.',
            'data' => new PaymentResource($payment->load('tenant')),
        ]);
    }
}
