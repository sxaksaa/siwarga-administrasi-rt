<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseOccupancy extends Model
{
    use HasFactory;

    protected $table = 'riwayat_hunian';

    protected $fillable = ['rumah_id', 'penghuni_id', 'mulai_tinggal', 'selesai_tinggal', 'catatan'];

    protected function casts(): array
    {
        return ['mulai_tinggal' => 'date', 'selesai_tinggal' => 'date'];
    }

    /**
     * Batasi kueri pada hunian yang rentang tanggalnya beririsan.
     *
     * Rentang tanggal bersifat inklusif. Jika tanggal selesai hunian lama sama
     * dengan tanggal mulai hunian baru, keduanya tetap dianggap bertumpang tindih.
     */
    public function scopeOverlapping(Builder $query, string $startDate, ?string $endDate = null): Builder
    {
        return $query
            ->when($endDate, fn (Builder $query) => $query->whereDate('mulai_tinggal', '<=', $endDate))
            ->where(function (Builder $query) use ($startDate) {
                $query->whereNull('selesai_tinggal')
                    ->orWhereDate('selesai_tinggal', '>=', $startDate);
            });
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class, 'rumah_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'penghuni_id');
    }
}
