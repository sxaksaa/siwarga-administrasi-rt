<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class House extends Model
{
    use HasFactory;

    protected $table = 'rumah';

    protected $fillable = ['nomor_rumah', 'alamat', 'catatan'];

    public function occupancies(): HasMany
    {
        return $this->hasMany(HouseOccupancy::class, 'rumah_id');
    }

    public function activeOccupancy(): HasOne
    {
        return $this->hasOne(HouseOccupancy::class, 'rumah_id')->whereNull('selesai_tinggal')->latestOfMany('mulai_tinggal');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'rumah_id');
    }
}
