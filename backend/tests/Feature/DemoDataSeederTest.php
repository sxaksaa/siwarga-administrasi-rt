<?php

namespace Tests\Feature;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_demo_lengkap_dan_tidak_berlipat_saat_dijalankan_ulang(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('penghuni', 18);
        $this->assertDatabaseCount('rumah', 20);
        $this->assertSame(15, DB::table('penghuni')->where('jenis_penghuni', 'tetap')->count());
        $this->assertSame(3, DB::table('penghuni')->where('jenis_penghuni', 'kontrak')->count());
        $this->assertSame(18, DB::table('riwayat_hunian')->whereNull('selesai_tinggal')->count());
        $this->assertSame(2, DB::table('rumah')->whereNotIn(
            'id',
            DB::table('riwayat_hunian')->whereNull('selesai_tinggal')->select('rumah_id'),
        )->count());
        $this->assertDatabaseCount('tagihan', 252);
        $this->assertDatabaseCount('pembayaran', 108);
        $this->assertSame(108, DB::table('pembayaran')->where('nomor_bukti', 'like', 'BYR-%')->count());
        $this->assertSame(0, DB::table('pembayaran')->where('nomor_bukti', 'like', 'DEMO-%')->count());
        $this->assertDatabaseCount('alokasi_pembayaran', 216);
        $this->assertDatabaseCount('pengeluaran', 16);

        $photoPaths = DB::table('penghuni')->pluck('foto_ktp_path');
        $this->assertCount(18, $photoPaths);
        $this->assertCount(18, $photoPaths->unique());
        $photoPaths->each(fn (string $path) => Storage::disk('local')->assertExists($path));
    }
}
