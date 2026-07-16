<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreResidentRequest extends FormRequest
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
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'foto_ktp' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'jenis_penghuni' => ['required', 'in:tetap,kontrak'],
            'nomor_telepon' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\-\s]+$/'],
            'sudah_menikah' => ['required', 'boolean'],
        ];
    }
}
