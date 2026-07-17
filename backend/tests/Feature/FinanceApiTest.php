<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\DueType;
use App\Models\Expense;
use App\Models\House;
use App\Models\HouseOccupancy;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
        DueType::create(['kode' => 'SATPAM', 'nama' => 'Iuran Satpam', 'nominal_default' => 100000, 'aktif' => true]);
        DueType::create(['kode' => 'KEBERSIHAN', 'nama' => 'Iuran Kebersihan', 'nominal_default' => 15000, 'aktif' => true]);
    }

    public function test_tagihan_hanya_dibuat_untuk_rumah_yang_dihuni_dan_tidak_duplikat(): void
    {
        [$occupiedHouse] = $this->createOccupiedHouse();
        House::create(['nomor_rumah' => 'A-02']);

        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])
            ->assertOk()->assertJson(['dibuat' => 2, 'dilewati_karena_sudah_ada' => 0]);

        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])
            ->assertOk()->assertJson(['dibuat' => 0, 'dilewati_karena_sudah_ada' => 2]);

        $this->assertDatabaseCount('tagihan', 2);
        $this->assertDatabaseMissing('tagihan', ['rumah_id' => House::where('nomor_rumah', 'A-02')->value('id')]);
        $this->assertDatabaseHas('tagihan', ['rumah_id' => $occupiedHouse->id, 'status' => 'belum_lunas']);
    }

    public function test_satu_pembayaran_dapat_melunasi_beberapa_tagihan(): void
    {
        [$house, $resident] = $this->createOccupiedHouse();
        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])->assertOk();
        $bills = Bill::orderBy('nominal')->get();

        $this->postJson('/api/pembayaran', [
            'rumah_id' => $house->id,
            'penghuni_id' => $resident->id,
            'tanggal_bayar' => '2026-07-05 09:00:00',
            'alokasi' => $bills->map(fn ($bill) => ['tagihan_id' => $bill->id, 'nominal' => (float) $bill->nominal])->all(),
        ])->assertSuccessful()->assertJsonPath('data.total_bayar', 115000);

        $this->assertDatabaseCount('pembayaran', 1);
        $this->assertDatabaseCount('alokasi_pembayaran', 2);
        $this->assertSame(2, Bill::where('status', 'lunas')->count());
    }

    public function test_detail_rumah_memuat_riwayat_tagihan_beserta_penghuni_dan_status(): void
    {
        [$house, $resident] = $this->createOccupiedHouse();
        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])->assertOk();
        $bill = Bill::whereHas('dueType', fn ($query) => $query->where('kode', 'SATPAM'))->firstOrFail();

        $this->postJson('/api/pembayaran', [
            'rumah_id' => $house->id,
            'penghuni_id' => $resident->id,
            'tanggal_bayar' => '2026-07-05',
            'alokasi' => [['tagihan_id' => $bill->id, 'nominal' => (float) $bill->nominal]],
        ])->assertSuccessful();

        $this->getJson("/api/rumah/{$house->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.riwayat_tagihan')
            ->assertJsonFragment([
                'nama_penghuni' => $resident->nama_lengkap,
                'jenis_iuran' => 'Iuran Satpam',
                'nominal_terbayar' => 100000,
                'status' => 'lunas',
            ])
            ->assertJsonFragment([
                'nama_penghuni' => $resident->nama_lengkap,
                'jenis_iuran' => 'Iuran Kebersihan',
                'nominal_terbayar' => 0,
                'status' => 'belum_lunas',
            ]);
    }

    public function test_tagihan_harus_dibayar_lunas_dan_nominal_tidak_boleh_diubah(): void
    {
        [$house, $resident] = $this->createOccupiedHouse();
        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])->assertOk();
        $bill = Bill::whereHas('dueType', fn ($query) => $query->where('kode', 'SATPAM'))->firstOrFail();

        $payload = [
            'rumah_id' => $house->id,
            'penghuni_id' => $resident->id,
            'tanggal_bayar' => '2026-07-05',
            'alokasi' => [['tagihan_id' => $bill->id, 'nominal' => 40000]],
        ];
        $this->postJson('/api/pembayaran', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('alokasi');
        $this->assertDatabaseHas('tagihan', ['id' => $bill->id, 'nominal_terbayar' => 0, 'status' => 'belum_lunas']);

        $payload['alokasi'][0]['nominal'] = 100000;
        $this->postJson('/api/pembayaran', $payload)
            ->assertSuccessful();
        $this->assertDatabaseHas('tagihan', ['id' => $bill->id, 'nominal_terbayar' => 100000, 'status' => 'lunas']);
        $this->assertDatabaseCount('pembayaran', 1);
    }

    public function test_tagihan_tahunan_dimulai_dari_bulan_pilihan_dan_satpam_tetap_bulanan(): void
    {
        [$house, $resident] = $this->createOccupiedHouse();

        $payload = ['rumah_id' => $house->id, 'periode_awal' => '2026-07', 'durasi' => 12];
        $this->postJson('/api/pembayaran/siapkan-tagihan', $payload)
            ->assertOk()
            ->assertJsonPath('data.dibuat', 13)
            ->assertJsonCount(13, 'data.tagihan_ids');

        $this->assertSame(1, Bill::whereHas('dueType', fn ($query) => $query->where('kode', 'SATPAM'))->count());
        $this->assertSame(12, Bill::whereHas('dueType', fn ($query) => $query->where('kode', 'KEBERSIHAN'))->count());
        $this->assertDatabaseHas('tagihan', [
            'penghuni_id' => $resident->id,
            'periode_tagihan' => '2026-07-01',
            'nama_penghuni_snapshot' => $resident->nama_lengkap,
        ]);
        $this->assertDatabaseHas('tagihan', ['periode_tagihan' => '2027-06-01']);
        $this->assertDatabaseMissing('tagihan', ['periode_tagihan' => '2027-07-01']);

        $this->postJson('/api/pembayaran/siapkan-tagihan', $payload)
            ->assertOk()
            ->assertJsonPath('data.dibuat', 0)
            ->assertJsonPath('data.sudah_tersedia', 13);
        $this->assertDatabaseCount('tagihan', 13);
    }

    public function test_pembayar_yang_tidak_terkait_dengan_rumah_atau_tagihan_ditolak(): void
    {
        [$house] = $this->createOccupiedHouse();
        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])->assertOk();
        $bill = Bill::firstOrFail();
        $unrelatedResident = Resident::create([
            'nama_lengkap' => 'Orang Luar', 'foto_ktp_path' => 'ktp/luar.jpg',
            'jenis_penghuni' => 'kontrak', 'nomor_telepon' => '081200000001', 'sudah_menikah' => false,
        ]);

        $this->postJson('/api/pembayaran', [
            'rumah_id' => $house->id,
            'penghuni_id' => $unrelatedResident->id,
            'tanggal_bayar' => '2026-07-05',
            'alokasi' => [['tagihan_id' => $bill->id, 'nominal' => (float) $bill->nominal]],
        ])->assertUnprocessable()->assertJsonValidationErrors('penghuni_id');

        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_penghuni_historis_pada_tagihan_dan_penghuni_aktif_boleh_membayar(): void
    {
        [$house, $previousResident] = $this->createOccupiedHouse();
        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])->assertOk();
        HouseOccupancy::where('rumah_id', $house->id)->update(['selesai_tinggal' => '2026-07-15']);
        $activeResident = Resident::create([
            'nama_lengkap' => 'Siti Aktif', 'foto_ktp_path' => 'ktp/siti.jpg',
            'jenis_penghuni' => 'tetap', 'nomor_telepon' => '081200000002', 'sudah_menikah' => true,
        ]);
        HouseOccupancy::create([
            'rumah_id' => $house->id, 'penghuni_id' => $activeResident->id, 'mulai_tinggal' => '2026-07-16',
        ]);
        $bills = Bill::orderBy('id')->get();

        foreach ([[$previousResident, $bills[0]], [$activeResident, $bills[1]]] as [$payer, $bill]) {
            $this->postJson('/api/pembayaran', [
                'rumah_id' => $house->id, 'penghuni_id' => $payer->id,
                'tanggal_bayar' => '2026-07-20',
                'alokasi' => [['tagihan_id' => $bill->id, 'nominal' => (float) $bill->nominal]],
            ])->assertSuccessful();
        }

        $this->assertDatabaseCount('pembayaran', 2);
    }

    public function test_opsi_pembayaran_memuat_semua_tagihan_satu_rumah_tanpa_batas_seratus(): void
    {
        [$house, $resident] = $this->createOccupiedHouse();
        $dueType = DueType::where('kode', 'SATPAM')->firstOrFail();
        $firstPeriod = now()->startOfMonth()->subMonths(100);

        foreach (range(0, 100) as $month) {
            Bill::create([
                'rumah_id' => $house->id, 'penghuni_id' => $resident->id, 'jenis_iuran_id' => $dueType->id,
                'periode_tagihan' => $firstPeriod->copy()->addMonths($month)->toDateString(),
                'nominal' => 100000, 'jatuh_tempo' => $firstPeriod->copy()->addMonths($month)->day(10)->toDateString(),
                'nama_penghuni_snapshot' => $resident->nama_lengkap, 'jenis_penghuni_snapshot' => $resident->jenis_penghuni,
            ]);
        }

        $this->getJson("/api/pembayaran/opsi?rumah_id={$house->id}")
            ->assertOk()
            ->assertJsonCount(101, 'data.tagihan')
            ->assertJsonCount(1, 'data.pembayar')
            ->assertJsonPath('data.pembayar.0.penghuni_aktif', true);
    }

    public function test_tagihan_pergantian_penghuni_memprioritaskan_penghuni_pada_awal_bulan(): void
    {
        [$house, $firstResident] = $this->createOccupiedHouse();
        HouseOccupancy::where('rumah_id', $house->id)->update(['selesai_tinggal' => '2026-07-10']);
        $nextResident = Resident::create([
            'nama_lengkap' => 'Siti Penghuni Baru', 'foto_ktp_path' => 'ktp/siti-baru.jpg',
            'jenis_penghuni' => 'kontrak', 'nomor_telepon' => '081200000003', 'sudah_menikah' => false,
        ]);
        HouseOccupancy::create([
            'rumah_id' => $house->id, 'penghuni_id' => $nextResident->id, 'mulai_tinggal' => '2026-07-11',
        ]);

        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])
            ->assertOk()
            ->assertJson(['dibuat' => 2]);

        $this->assertSame(2, Bill::where('penghuni_id', $firstResident->id)->count());
        $this->assertSame(0, Bill::where('penghuni_id', $nextResident->id)->count());
    }

    public function test_tagihan_pergantian_pada_awal_bulan_diberikan_ke_penghuni_baru(): void
    {
        [$house, $firstResident] = $this->createOccupiedHouse();
        HouseOccupancy::where('rumah_id', $house->id)->update(['selesai_tinggal' => '2026-07-01']);
        $nextResident = Resident::create([
            'nama_lengkap' => 'Siti Penghuni Baru', 'foto_ktp_path' => 'ktp/siti-baru.jpg',
            'jenis_penghuni' => 'kontrak', 'nomor_telepon' => '081200000003', 'sudah_menikah' => false,
        ]);
        HouseOccupancy::create([
            'rumah_id' => $house->id, 'penghuni_id' => $nextResident->id, 'mulai_tinggal' => '2026-07-01',
        ]);

        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])
            ->assertOk()
            ->assertJson(['dibuat' => 2]);

        $this->assertSame(0, Bill::where('penghuni_id', $firstResident->id)->count());
        $this->assertSame(2, Bill::where('penghuni_id', $nextResident->id)->count());
    }

    public function test_laporan_bulanan_dan_tahunan_menghitung_saldo(): void
    {
        [$house, $resident] = $this->createOccupiedHouse();
        $this->postJson('/api/tagihan/buat-bulanan', ['periode' => '2026-07'])->assertOk();
        $bill = Bill::where('nominal', 100000)->firstOrFail();
        $this->postJson('/api/pembayaran', [
            'rumah_id' => $house->id,
            'penghuni_id' => $resident->id,
            'tanggal_bayar' => '2026-07-05',
            'alokasi' => [['tagihan_id' => $bill->id, 'nominal' => 100000]],
        ])->assertSuccessful();
        Expense::create([
            'kategori' => 'Operasional', 'keterangan' => 'Token listrik', 'nominal' => 25000,
            'tanggal_pengeluaran' => '2026-07-10', 'rutin' => true,
        ]);

        $this->getJson('/api/laporan/bulanan?bulan=2026-07')->assertOk()->assertJson([
            'total_pemasukan' => 100000,
            'total_pengeluaran' => 25000,
            'saldo_akhir' => 75000,
        ]);
        $this->getJson('/api/laporan/tahunan?tahun=2026')->assertOk()->assertJson([
            'total_pemasukan' => 100000,
            'total_pengeluaran' => 25000,
            'saldo_akhir' => 75000,
        ])->assertJsonPath('bulanan.6.saldo', 75000);
    }

    public function test_data_pengeluaran_dapat_diperbarui(): void
    {
        $expense = Expense::create([
            'kategori' => 'Operasional', 'keterangan' => 'Token listrik', 'nominal' => 150000,
            'tanggal_pengeluaran' => '2026-07-10', 'rutin' => true,
        ]);

        $this->patchJson("/api/pengeluaran/{$expense->id}", [
            'kategori' => 'Keamanan', 'keterangan' => 'Perbaikan pos satpam', 'nominal' => 250000,
            'tanggal_pengeluaran' => '2026-07-11', 'rutin' => false, 'catatan' => 'Nominal dikoreksi.',
        ])->assertOk()
            ->assertJsonPath('data.keterangan', 'Perbaikan pos satpam')
            ->assertJsonPath('data.nominal', 250000);

        $this->assertDatabaseHas('pengeluaran', [
            'id' => $expense->id, 'kategori' => 'Keamanan', 'nominal' => 250000, 'rutin' => false,
        ]);
    }

    private function createOccupiedHouse(): array
    {
        $house = House::create(['nomor_rumah' => 'A-01']);
        $resident = Resident::create([
            'nama_lengkap' => 'Budi Santoso', 'foto_ktp_path' => 'ktp/budi.jpg',
            'jenis_penghuni' => 'tetap', 'nomor_telepon' => '081234567890', 'sudah_menikah' => true,
        ]);
        HouseOccupancy::create([
            'rumah_id' => $house->id, 'penghuni_id' => $resident->id, 'mulai_tinggal' => '2026-01-01',
        ]);

        return [$house, $resident];
    }
}
