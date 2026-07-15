<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use HasFactory;

    protected $table = 'alokasi_pembayaran';

    protected $fillable = ['pembayaran_id', 'tagihan_id', 'nominal'];

    protected function casts(): array
    {
        return ['nominal' => 'decimal:2'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'pembayaran_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'tagihan_id');
    }
}
