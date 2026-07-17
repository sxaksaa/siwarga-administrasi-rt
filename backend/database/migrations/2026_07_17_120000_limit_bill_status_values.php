<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tagihan')) {
            return;
        }

        DB::table('tagihan')
            ->whereNotIn('status', ['belum_lunas', 'lunas'])
            ->update([
                'status' => DB::raw("CASE WHEN nominal_terbayar >= nominal THEN 'lunas' ELSE 'belum_lunas' END"),
            ]);

        Schema::table('tagihan', function (Blueprint $table) {
            $table->enum('status', ['belum_lunas', 'lunas'])
                ->default('belum_lunas')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dua status ini sengaja dipertahankan agar aturan pelunasan tidak berubah saat rollback.
    }
};
