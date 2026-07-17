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
        if (! Schema::hasTable('pembayaran') || ! Schema::hasColumn('pembayaran', 'metode_pembayaran')) {
            return;
        }

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn('metode_pembayaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('pembayaran') || Schema::hasColumn('pembayaran', 'metode_pembayaran')) {
            return;
        }

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('metode_pembayaran')->default('tunai')->after('nama_pembayar_snapshot');
        });
    }
};
