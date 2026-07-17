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

        $this->assertDatabaseCount('penghuni', 15);
        $this->assertDatabaseCount('rumah', 20);
        $this->assertSame(15, DB::table('riwayat_hunian')->whereNull('selesai_tinggal')->count());
        $this->assertDatabaseCount('tagihan', 210);
        $this->assertDatabaseCount('pembayaran', 93);
        $this->assertDatabaseCount('alokasi_pembayaran', 186);
        $this->assertDatabaseCount('pengeluaran', 16);

        $photoPaths = DB::table('penghuni')->pluck('foto_ktp_path');
        $this->assertCount(15, $photoPaths);
        $this->assertCount(15, $photoPaths->unique());
        $photoPaths->each(fn (string $path) => Storage::disk('local')->assertExists($path));
    }
}
