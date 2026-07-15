<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
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
            'kategori' => ['sometimes', 'required', 'string', 'max:100'],
            'keterangan' => ['sometimes', 'required', 'string', 'max:255'],
            'nominal' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'tanggal_pengeluaran' => ['sometimes', 'required', 'date'],
            'rutin' => ['sometimes', 'required', 'boolean'],
            'bukti' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'catatan' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
