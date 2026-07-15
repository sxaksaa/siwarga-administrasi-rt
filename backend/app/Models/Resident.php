<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resident extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'penghuni';

    protected $fillable = ['nama_lengkap', 'foto_ktp_path', 'jenis_penghuni', 'nomor_telepon', 'sudah_menikah'];

    protected function casts(): array
    {
        return ['sudah_menikah' => 'boolean'];
    }

    public function occupancies(): HasMany
    {
        return $this->hasMany(HouseOccupancy::class, 'penghuni_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'penghuni_id');
    }
}
