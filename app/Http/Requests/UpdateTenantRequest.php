<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Will be protected by Sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_id' => ['sometimes', 'required', 'exists:rooms,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'whatsapp_number' => ['sometimes', 'required', 'string', 'regex:/^(08|628)[0-9]{8,13}$/'],
            'entry_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', 'in:active,inactive'],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'room_id.exists' => 'Kamar tidak ditemukan.',
            'whatsapp_number.regex' => 'Format nomor WhatsApp tidak valid. Gunakan format 08xxx atau 628xxx.',
        ];
    }
}
