<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kategori' => $this->kategori,
            'keterangan' => $this->keterangan,
            'nominal' => (float) $this->nominal,
            'tanggal_pengeluaran' => $this->tanggal_pengeluaran?->toDateString(),
            'rutin' => $this->rutin,
            'bukti_url' => $this->bukti_path ? asset('storage/'.$this->bukti_path) : null,
            'catatan' => $this->catatan,
        ];
    }
}
