<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseResource extends JsonResource
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
            'nomor_rumah' => $this->nomor_rumah,
            'alamat' => $this->alamat,
            'catatan' => $this->catatan,
            'status' => $this->activeOccupancy ? 'dihuni' : 'tidak_dihuni',
            'penghuni_aktif' => $this->activeOccupancy
                ? new HouseOccupancyResource($this->activeOccupancy)
                : null,
            'riwayat_hunian' => HouseOccupancyResource::collection($this->whenLoaded('occupancies')),
            'riwayat_tagihan' => BillResource::collection($this->whenLoaded('bills')),
            'dibuat_pada' => $this->created_at?->toISOString(),
            'diperbarui_pada' => $this->updated_at?->toISOString(),
        ];
    }
}
