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
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rumah_id')->constrained('rumah')->restrictOnDelete();
            $table->foreignId('penghuni_id')->nullable()->constrained('penghuni')->nullOnDelete();
            $table->foreignId('jenis_iuran_id')->constrained('jenis_iuran')->restrictOnDelete();
            $table->date('periode_tagihan');
            $table->decimal('nominal', 14, 2);
            $table->decimal('nominal_terbayar', 14, 2)->default(0);
            $table->enum('status', ['belum_lunas', 'lunas'])->default('belum_lunas');
            $table->date('jatuh_tempo')->nullable();
            $table->string('nama_penghuni_snapshot');
            $table->enum('jenis_penghuni_snapshot', ['tetap', 'kontrak']);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['rumah_id', 'jenis_iuran_id', 'periode_tagihan']);
            $table->index(['periode_tagihan', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
