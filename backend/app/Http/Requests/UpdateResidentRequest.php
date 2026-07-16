<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateResidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nama_lengkap') && is_string($this->input('nama_lengkap'))) {
            $this->merge([
                'nama_lengkap' => Str::of($this->input('nama_lengkap'))->squish()->title()->toString(),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_lengkap' => ['sometimes', 'required', 'string', 'max:255'],
            'foto_ktp' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'jenis_penghuni' => ['sometimes', 'required', 'in:tetap,kontrak'],
            'nomor_telepon' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'regex:/^(?=(?:\D*\d){10,15}\D*$)\+?[0-9()\-\s]+$/',
            ],
            'sudah_menikah' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nomor_telepon.regex' => 'Nomor telepon harus berisi 10 sampai 15 digit dan hanya boleh menggunakan angka, +, spasi, tanda kurung, atau tanda hubung.',
        ];
    }
}
