<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DueType extends Model
{
    use HasFactory;

    protected $table = 'jenis_iuran';

    protected $fillable = ['kode', 'nama', 'nominal_default', 'aktif'];

    protected function casts(): array
    {
        return ['nominal_default' => 'decimal:2', 'aktif' => 'boolean'];
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'jenis_iuran_id');
    }
}
