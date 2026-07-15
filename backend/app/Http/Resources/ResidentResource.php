<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentResource extends JsonResource
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
            'nama_lengkap' => $this->nama_lengkap,
            'foto_ktp_tersedia' => (bool) $this->foto_ktp_path,
            'foto_ktp_url' => $this->foto_ktp_path ? url("/api/penghuni/{$this->id}/foto-ktp") : null,
            'jenis_penghuni' => $this->jenis_penghuni,
            'nomor_telepon' => $this->nomor_telepon,
            'sudah_menikah' => $this->sudah_menikah,
            'dibuat_pada' => $this->created_at?->toISOString(),
            'diperbarui_pada' => $this->updated_at?->toISOString(),
        ];
    }
}
