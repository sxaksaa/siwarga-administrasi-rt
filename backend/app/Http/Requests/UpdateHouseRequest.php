<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHouseRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nomor_rumah' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('rumah', 'nomor_rumah')->ignore($this->route('house')),
            ],
            'alamat' => ['sometimes', 'nullable', 'string', 'max:255'],
            'catatan' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
