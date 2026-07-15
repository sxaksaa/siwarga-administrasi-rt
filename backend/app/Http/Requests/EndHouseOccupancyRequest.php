<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EndHouseOccupancyRequest extends FormRequest
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
            'selesai_tinggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get the validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selesai_tinggal.required' => 'Tanggal selesai tinggal wajib diisi.',
            'selesai_tinggal.date_format' => 'Tanggal selesai tinggal harus menggunakan format YYYY-MM-DD.',
            'selesai_tinggal.before_or_equal' => 'Tanggal selesai tinggal tidak boleh melebihi hari ini.',
        ];
    }
}
