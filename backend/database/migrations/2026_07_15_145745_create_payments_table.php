<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_bukti')->unique();
            $table->foreignId('rumah_id')->constrained('rumah')->restrictOnDelete();
            $table->foreignId('penghuni_id')->nullable()->constrained('penghuni')->nullOnDelete();
            $table->dateTime('tanggal_bayar');
            $table->decimal('total_bayar', 14, 2);
            $table->string('nama_pembayar_snapshot');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('tanggal_bayar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
