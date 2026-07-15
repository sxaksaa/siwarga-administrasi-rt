<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengeluaran';

    protected $fillable = [
        'kategori', 'keterangan', 'nominal', 'tanggal_pengeluaran', 'rutin', 'bukti_path', 'catatan',
    ];

    protected function casts(): array
    {
        return ['nominal' => 'decimal:2', 'tanggal_pengeluaran' => 'date', 'rutin' => 'boolean'];
    }
}
