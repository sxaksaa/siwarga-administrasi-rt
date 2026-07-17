<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'nomor_bukti', 'rumah_id', 'penghuni_id', 'tanggal_bayar', 'total_bayar',
        'nama_pembayar_snapshot', 'catatan',
    ];

    protected function casts(): array
    {
        return ['tanggal_bayar' => 'datetime', 'total_bayar' => 'decimal:2'];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class, 'rumah_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'penghuni_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'pembayaran_id');
    }
}
