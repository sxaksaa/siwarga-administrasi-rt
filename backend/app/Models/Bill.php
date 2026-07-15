<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    protected $table = 'tagihan';

    protected $fillable = [
        'rumah_id', 'penghuni_id', 'jenis_iuran_id', 'periode_tagihan', 'nominal', 'nominal_terbayar',
        'status', 'jatuh_tempo', 'nama_penghuni_snapshot', 'jenis_penghuni_snapshot', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'periode_tagihan' => 'date', 'jatuh_tempo' => 'date',
            'nominal' => 'decimal:2', 'nominal_terbayar' => 'decimal:2',
        ];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class, 'rumah_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'penghuni_id');
    }

    public function dueType(): BelongsTo
    {
        return $this->belongsTo(DueType::class, 'jenis_iuran_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'tagihan_id');
    }
}
