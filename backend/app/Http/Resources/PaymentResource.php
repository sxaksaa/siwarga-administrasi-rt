<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'nomor_bukti' => $this->nomor_bukti,
            'rumah_id' => $this->rumah_id,
            'nomor_rumah' => $this->whenLoaded('house', fn () => $this->house->nomor_rumah),
            'penghuni_id' => $this->penghuni_id,
            'nama_pembayar' => $this->nama_pembayar_snapshot,
            'tanggal_bayar' => $this->tanggal_bayar?->toISOString(),
            'total_bayar' => (float) $this->total_bayar,
            'catatan' => $this->catatan,
            'alokasi' => $this->whenLoaded('allocations', fn () => $this->allocations->map(fn ($allocation) => [
                'tagihan_id' => $allocation->tagihan_id,
                'nominal' => (float) $allocation->nominal,
                'tagihan' => new BillResource($allocation->bill),
            ])),
        ];
    }
}
