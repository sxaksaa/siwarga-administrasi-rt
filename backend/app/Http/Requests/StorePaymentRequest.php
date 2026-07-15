<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'rumah_id' => ['required', 'integer', 'exists:rumah,id'],
            'penghuni_id' => ['required', 'integer', 'exists:penghuni,id'],
            'tanggal_bayar' => ['required', 'date'],
            'metode_pembayaran' => ['required', 'in:tunai,transfer'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'alokasi' => ['required', 'array', 'min:1'],
            'alokasi.*.tagihan_id' => ['required', 'integer', 'distinct', 'exists:tagihan,id'],
            'alokasi.*.nominal' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * Pesan validasi yang tampil langsung di formulir pembayaran.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rumah_id.required' => 'Rumah wajib dipilih.',
            'penghuni_id.required' => 'Pembayar wajib dipilih.',
            'penghuni_id.exists' => 'Data pembayar tidak ditemukan.',
            'tanggal_bayar.required' => 'Tanggal pembayaran wajib diisi.',
            'metode_pembayaran.required' => 'Metode pembayaran wajib dipilih.',
            'metode_pembayaran.in' => 'Metode pembayaran harus tunai atau transfer.',
            'alokasi.required' => 'Pilih minimal satu tagihan.',
            'alokasi.min' => 'Pilih minimal satu tagihan.',
            'alokasi.*.nominal.gt' => 'Nominal pembayaran setiap tagihan harus lebih dari nol.',
        ];
    }
}
