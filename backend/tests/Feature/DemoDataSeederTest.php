<?php

namespace Tests\Feature;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        $this->assertDatabaseCount('penghuni', 19);
        $this->assertDatabaseHas('users', ['name' => 'Admin', 'email' => 'admin@gmail.com']);
        $this->assertTrue(Hash::check('admin', DB::table('users')->where('email', 'admin@gmail.com')->value('password')));
        $this->assertDatabaseCount('rumah', 20);
        $this->assertSame(15, DB::table('penghuni')->where('jenis_penghuni', 'tetap')->count());
        $this->assertSame(4, DB::table('penghuni')->where('jenis_penghuni', 'kontrak')->count());
        $this->assertSame(18, DB::table('riwayat_hunian')->whereNull('selesai_tinggal')->count());
        $this->assertSame(15, DB::table('riwayat_hunian as rh')
            ->join('penghuni as p', 'p.id', '=', 'rh.penghuni_id')
            ->whereNull('rh.selesai_tinggal')
            ->where('p.jenis_penghuni', 'tetap')
            ->count());
        $this->assertSame(3, DB::table('riwayat_hunian as rh')
            ->join('penghuni as p', 'p.id', '=', 'rh.penghuni_id')
            ->whereNull('rh.selesai_tinggal')
            ->where('p.jenis_penghuni', 'kontrak')
            ->count());
        $this->assertSame(2, DB::table('rumah')->whereNotIn(
            'id',
            DB::table('riwayat_hunian')->whereNull('selesai_tinggal')->select('rumah_id'),
        )->count());
        $this->assertDatabaseHas('riwayat_hunian', [
            'rumah_id' => DB::table('rumah')->where('nomor_rumah', 'A-17')->value('id'),
            'penghuni_id' => DB::table('penghuni')->where('nama_lengkap', 'Raka Wijaya')->value('id'),
            'mulai_tinggal' => '2026-01-01',
            'selesai_tinggal' => '2026-07-17',
        ]);
        $this->assertDatabaseHas('riwayat_hunian', [
            'rumah_id' => DB::table('rumah')->where('nomor_rumah', 'A-17')->value('id'),
            'penghuni_id' => DB::table('penghuni')->where('nama_lengkap', 'Atmint Loh')->value('id'),
            'mulai_tinggal' => '2026-07-17',
            'selesai_tinggal' => null,
        ]);
        $this->assertDatabaseCount('tagihan', 263);
        $this->assertDatabaseCount('pembayaran', 109);
        $this->assertSame(109, DB::table('pembayaran')->where('nomor_bukti', 'like', 'BYR-%')->count());
        $this->assertSame(0, DB::table('pembayaran')->where('nomor_bukti', 'like', 'DEMO-%')->count());
        $this->assertDatabaseHas('pembayaran', [
            'nomor_bukti' => 'BYR-20260717-KQ4GVI',
            'penghuni_id' => DB::table('penghuni')->where('nama_lengkap', 'Atmint Loh')->value('id'),
            'total_bayar' => 115000,
        ]);
        $this->assertDatabaseCount('alokasi_pembayaran', 218);
        $this->assertDatabaseCount('pengeluaran', 16);

        $photoPaths = DB::table('penghuni')->pluck('foto_ktp_path');
        $this->assertCount(19, $photoPaths);
        $this->assertCount(19, $photoPaths->unique());
        $photoPaths->each(fn (string $path) => Storage::disk('local')->assertExists($path));
    }

    public function test_seeder_mempertahankan_pergantian_penghuni_dan_riwayat_pembayaran(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $houseId = DB::table('rumah')->where('nomor_rumah', 'A-17')->value('id');
        $atmintId = DB::table('penghuni')->where('nama_lengkap', 'Atmint Loh')->value('id');
        $replacementId = DB::table('penghuni')->insertGetId([
            'nama_lengkap' => 'Penghuni Pengganti',
            'foto_ktp_path' => 'ktp/pengganti.png',
            'jenis_penghuni' => 'kontrak',
            'nomor_telepon' => '081299999999',
            'sudah_menikah' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('riwayat_hunian')
            ->where('rumah_id', $houseId)
            ->where('penghuni_id', $atmintId)
            ->whereNull('selesai_tinggal')
            ->update(['selesai_tinggal' => '2026-08-01', 'updated_at' => now()]);

        DB::table('riwayat_hunian')->insert([
            'rumah_id' => $houseId,
            'penghuni_id' => $replacementId,
            'mulai_tinggal' => '2026-08-01',
            'selesai_tinggal' => null,
            'catatan' => 'Pergantian melalui aplikasi.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(1, DB::table('riwayat_hunian')->where('rumah_id', $houseId)->whereNull('selesai_tinggal')->count());
        $this->assertDatabaseHas('riwayat_hunian', [
            'rumah_id' => $houseId,
            'penghuni_id' => $replacementId,
            'mulai_tinggal' => '2026-08-01',
            'selesai_tinggal' => null,
        ]);
        $this->assertDatabaseMissing('riwayat_hunian', [
            'rumah_id' => $houseId,
            'penghuni_id' => $atmintId,
            'selesai_tinggal' => null,
        ]);

        $this->assertSame(5, DB::table('pembayaran')
            ->where('rumah_id', $houseId)
            ->where('nama_pembayar_snapshot', 'Raka Wijaya')
            ->whereBetween('tanggal_bayar', ['2026-01-01', '2026-05-31 23:59:59'])
            ->count());
        $this->assertSame(0, DB::table('pembayaran')
            ->where('rumah_id', $houseId)
            ->where('penghuni_id', $replacementId)
            ->where('tanggal_bayar', '<', '2026-08-01')
            ->count());
        $this->assertDatabaseCount('tagihan', 263);
        $this->assertDatabaseCount('pembayaran', 109);
        $this->assertDatabaseCount('alokasi_pembayaran', 218);
    }

    public function test_seeder_membersihkan_duplikat_aktif_dari_versi_lama(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $houseId = DB::table('rumah')->where('nomor_rumah', 'A-17')->value('id');
        $rakaId = DB::table('penghuni')->where('nama_lengkap', 'Raka Wijaya')->value('id');

        DB::table('riwayat_hunian')->insert([
            'rumah_id' => $houseId,
            'penghuni_id' => $rakaId,
            'mulai_tinggal' => '2026-01-01',
            'selesai_tinggal' => null,
            'catatan' => 'Duplikat dari seeder versi lama.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(2, DB::table('riwayat_hunian')->where('rumah_id', $houseId)->whereNull('selesai_tinggal')->count());

        $this->seed(DemoDataSeeder::class);

        $this->assertSame(1, DB::table('riwayat_hunian')->where('rumah_id', $houseId)->whereNull('selesai_tinggal')->count());
        $this->assertSame(0, DB::table('riwayat_hunian')
            ->where('rumah_id', $houseId)
            ->where('penghuni_id', $rakaId)
            ->whereNull('selesai_tinggal')
            ->count());
        $this->assertDatabaseCount('riwayat_hunian', 19);
    }
}
