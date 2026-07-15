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
        Schema::create('riwayat_hunian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rumah_id')->constrained('rumah')->cascadeOnDelete();
            $table->foreignId('penghuni_id')->constrained('penghuni')->restrictOnDelete();
            $table->date('mulai_tinggal');
            $table->date('selesai_tinggal')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['rumah_id', 'selesai_tinggal']);
            $table->index(['penghuni_id', 'selesai_tinggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_hunian');
    }
};
