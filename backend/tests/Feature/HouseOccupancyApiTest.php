<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\HouseOccupancy;
use App\Models\Resident;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HouseOccupancyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-07-15 12:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_aplikasi_menggunakan_lokalisasi_indonesia(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('id_ID', config('app.faker_locale'));
    }

    public function test_admin_dapat_menempatkan_dan_mengakhiri_masa_tinggal_penghuni(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $house = House::create(['nomor_rumah' => 'A-01']);
        $resident = Resident::create($this->residentData('Budi Santoso', '081111111111'));

        $create = $this->postJson("/api/rumah/{$house->id}/hunian", [
            'penghuni_id' => $resident->id,
            'mulai_tinggal' => '2026-01-01',
        ])->assertSuccessful()->assertJsonPath('data.aktif', true);

        $occupancyId = $create->json('data.id');

        $this->patchJson("/api/rumah/{$house->id}/hunian/{$occupancyId}/selesai", [
            'selesai_tinggal' => '2026-06-30',
        ])->assertOk()->assertJsonPath('data.aktif', false);

        $this->getJson("/api/rumah/{$house->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'tidak_dihuni')
            ->assertJsonCount(1, 'data.riwayat_hunian');

        $nextResident = Resident::create($this->residentData('Siti Aminah', '082222222222'));

        $this->postJson("/api/rumah/{$house->id}/hunian", [
            'penghuni_id' => $nextResident->id,
            'mulai_tinggal' => '2026-06-30',
        ])->assertCreated();

        $this->getJson("/api/rumah/{$house->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'dihuni')
            ->assertJsonPath('data.penghuni_aktif.penghuni.id', $nextResident->id)
            ->assertJsonCount(2, 'data.riwayat_hunian');
    }

    public function test_rumah_dan_penghuni_tidak_boleh_memiliki_hunian_aktif_ganda(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $houseOne = House::create(['nomor_rumah' => 'A-01']);
        $houseTwo = House::create(['nomor_rumah' => 'A-02']);
        $residentOne = Resident::create($this->residentData('Budi Santoso', '081111111111'));
        $residentTwo = Resident::create($this->residentData('Siti Aminah', '082222222222'));

        $this->postJson("/api/rumah/{$houseOne->id}/hunian", [
            'penghuni_id' => $residentOne->id, 'mulai_tinggal' => '2026-01-01',
        ])->assertSuccessful();

        $this->postJson("/api/rumah/{$houseOne->id}/hunian", [
            'penghuni_id' => $residentTwo->id, 'mulai_tinggal' => '2026-02-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('rumah');

        $this->postJson("/api/rumah/{$houseTwo->id}/hunian", [
            'penghuni_id' => $residentOne->id, 'mulai_tinggal' => '2026-02-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('penghuni_id');
    }

    public function test_penghuni_dapat_keluar_dan_diganti_pada_tanggal_mulai_yang_sama(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $house = House::create(['nomor_rumah' => 'A-01']);
        $residentOne = Resident::create($this->residentData('Budi Santoso', '081111111111'));
        $residentTwo = Resident::create($this->residentData('Siti Aminah', '082222222222'));

        $occupancyId = $this->postJson("/api/rumah/{$house->id}/hunian", [
            'penghuni_id' => $residentOne->id,
            'mulai_tinggal' => '2026-07-15',
        ])->assertCreated()->json('data.id');

        $this->patchJson("/api/rumah/{$house->id}/hunian/{$occupancyId}/selesai", [
            'selesai_tinggal' => '2026-07-15',
        ])->assertOk();

        $this->postJson("/api/rumah/{$house->id}/hunian", [
            'penghuni_id' => $residentTwo->id,
            'mulai_tinggal' => '2026-07-15',
        ])->assertCreated();
    }

    public function test_tanggal_mulai_dan_selesai_tinggal_tidak_boleh_di_masa_depan(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $house = House::create(['nomor_rumah' => 'A-01']);
        $resident = Resident::create($this->residentData('Budi Santoso', '081111111111'));

        $this->postJson("/api/rumah/{$house->id}/hunian", [
            'penghuni_id' => $resident->id,
            'mulai_tinggal' => '2026-07-16',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('mulai_tinggal');

        $occupancyId = $this->postJson("/api/rumah/{$house->id}/hunian", [
            'penghuni_id' => $resident->id,
            'mulai_tinggal' => '2026-07-15',
        ])->assertCreated()->json('data.id');

        $this->patchJson("/api/rumah/{$house->id}/hunian/{$occupancyId}/selesai", [
            'selesai_tinggal' => '2026-07-16',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('selesai_tinggal');
    }

    public function test_hunian_baru_tidak_boleh_tumpang_tindih_dengan_riwayat_rumah(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $house = House::create(['nomor_rumah' => 'A-01']);
        $residentOne = Resident::create($this->residentData('Budi Santoso', '081111111111'));
        $residentTwo = Resident::create($this->residentData('Siti Aminah', '082222222222'));

        HouseOccupancy::create([
            'rumah_id' => $house->id,
            'penghuni_id' => $residentOne->id,
            'mulai_tinggal' => '2026-01-01',
            'selesai_tinggal' => '2026-03-31',
        ]);

        $this->postJson("/api/rumah/{$house->id}/hunian", [
            'penghuni_id' => $residentTwo->id,
            'mulai_tinggal' => '2026-03-30',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('rumah');

        $this->postJson("/api/rumah/{$house->id}/hunian", [
            'penghuni_id' => $residentTwo->id,
            'mulai_tinggal' => '2026-03-31',
        ])->assertCreated();
    }

    public function test_penghuni_tidak_boleh_memiliki_riwayat_tumpang_tindih_di_rumah_lain(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $houseOne = House::create(['nomor_rumah' => 'A-01']);
        $houseTwo = House::create(['nomor_rumah' => 'A-02']);
        $resident = Resident::create($this->residentData('Budi Santoso', '081111111111'));

        HouseOccupancy::create([
            'rumah_id' => $houseOne->id,
            'penghuni_id' => $resident->id,
            'mulai_tinggal' => '2026-01-01',
            'selesai_tinggal' => '2026-03-31',
        ]);

        $this->postJson("/api/rumah/{$houseTwo->id}/hunian", [
            'penghuni_id' => $resident->id,
            'mulai_tinggal' => '2026-03-01',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('penghuni_id');

        $this->postJson("/api/rumah/{$houseTwo->id}/hunian", [
            'penghuni_id' => $resident->id,
            'mulai_tinggal' => '2026-03-31',
        ])->assertCreated();
    }

    public function test_tanggal_selesai_tidak_boleh_membuat_rentang_hunian_tumpang_tindih(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $house = House::create(['nomor_rumah' => 'A-01']);
        $residentOne = Resident::create($this->residentData('Budi Santoso', '081111111111'));
        $residentTwo = Resident::create($this->residentData('Siti Aminah', '082222222222'));

        $activeOccupancy = HouseOccupancy::create([
            'rumah_id' => $house->id,
            'penghuni_id' => $residentOne->id,
            'mulai_tinggal' => '2026-01-01',
        ]);
        HouseOccupancy::create([
            'rumah_id' => $house->id,
            'penghuni_id' => $residentTwo->id,
            'mulai_tinggal' => '2026-01-10',
            'selesai_tinggal' => '2026-01-20',
        ]);

        $this->patchJson("/api/rumah/{$house->id}/hunian/{$activeOccupancy->id}/selesai", [
            'selesai_tinggal' => '2026-01-11',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('selesai_tinggal');

        $this->patchJson("/api/rumah/{$house->id}/hunian/{$activeOccupancy->id}/selesai", [
            'selesai_tinggal' => '2026-01-10',
        ])->assertOk()
            ->assertJsonPath('data.aktif', false);
    }

    private function residentData(string $name, string $phone): array
    {
        return [
            'nama_lengkap' => $name,
            'foto_ktp_path' => 'ktp/contoh.jpg',
            'jenis_penghuni' => 'tetap',
            'nomor_telepon' => $phone,
            'sudah_menikah' => true,
        ];
    }
}
