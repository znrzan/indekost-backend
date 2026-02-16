<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'last_value' => ['sometimes', 'numeric', 'min:0'],
            'threshold' => ['sometimes', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:10'],
        ];
    }
}
