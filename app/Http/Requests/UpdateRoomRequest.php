<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
            'room_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'room_number')->ignore($this->room),
            ],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', 'in:available,occupied,maintenance'],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'room_number.unique' => 'Nomor kamar sudah terdaftar.',
            'price.min' => 'Harga tidak boleh negatif.',
            'status.in' => 'Status harus salah satu dari: available, occupied, maintenance.',
        ];
    }
}
