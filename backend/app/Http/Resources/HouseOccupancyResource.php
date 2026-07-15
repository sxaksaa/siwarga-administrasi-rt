<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseOccupancyResource extends JsonResource
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
            'penghuni_id' => $this->penghuni_id,
            'mulai_tinggal' => $this->mulai_tinggal?->toDateString(),
            'selesai_tinggal' => $this->selesai_tinggal?->toDateString(),
            'aktif' => $this->selesai_tinggal === null,
            'catatan' => $this->catatan,
            'penghuni' => new ResidentResource($this->whenLoaded('resident')),
        ];
    }
}
