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
        Schema::create('penghuni', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('foto_ktp_path')->nullable();
            $table->enum('jenis_penghuni', ['tetap', 'kontrak']);
            $table->string('nomor_telepon', 20);
            $table->boolean('sudah_menikah')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['jenis_penghuni', 'nama_lengkap']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penghuni');
    }
};
