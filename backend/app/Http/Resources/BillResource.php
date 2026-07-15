<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
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
            'rumah_id' => $this->rumah_id,
            'nomor_rumah' => $this->whenLoaded('house', fn () => $this->house->nomor_rumah),
            'penghuni_id' => $this->penghuni_id,
            'nama_penghuni' => $this->nama_penghuni_snapshot,
            'jenis_penghuni' => $this->jenis_penghuni_snapshot,
            'jenis_iuran_id' => $this->jenis_iuran_id,
            'jenis_iuran' => $this->whenLoaded('dueType', fn () => $this->dueType->nama),
            'periode_tagihan' => $this->periode_tagihan?->format('Y-m'),
            'nominal' => (float) $this->nominal,
            'nominal_terbayar' => (float) $this->nominal_terbayar,
            'sisa_tagihan' => (float) $this->nominal - (float) $this->nominal_terbayar,
            'status' => $this->status,
            'jatuh_tempo' => $this->jatuh_tempo?->toDateString(),
            'catatan' => $this->catatan,
        ];
    }
}
