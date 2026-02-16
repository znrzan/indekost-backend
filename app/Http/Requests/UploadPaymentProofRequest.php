<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentProofRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint for tenants
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'], // Format: YYYY-MM
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // Max 2MB
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'Data penyewa tidak ditemukan.',
            'period.regex' => 'Format periode harus YYYY-MM (contoh: 2026-02).',
            'proof.required' => 'Bukti pembayaran wajib diupload.',
            'proof.mimes' => 'Bukti pembayaran harus berformat JPG, PNG, atau PDF.',
            'proof.max' => 'Ukuran file maksimal 2MB.',
        ];
    }
}
