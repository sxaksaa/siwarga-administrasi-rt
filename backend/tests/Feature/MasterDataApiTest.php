<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_data_penghuni_dapat_diperbarui_dengan_method_spoof_tanpa_mengganti_foto(): void
    {
        $resident = Resident::create([
            'nama_lengkap' => 'Budi Santoso',
            'foto_ktp_path' => 'ktp/budi.jpg',
            'jenis_penghuni' => 'tetap',
            'nomor_telepon' => '081234567890',
            'sudah_menikah' => true,
        ]);

        $this->post("/api/penghuni/{$resident->id}", [
            '_method' => 'PATCH',
            'nama_lengkap' => 'Budi Santoso Baru',
            'nomor_telepon' => '089999999999',
            'jenis_penghuni' => 'kontrak',
            'sudah_menikah' => '0',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.nama_lengkap', 'Budi Santoso Baru');

        $this->assertDatabaseHas('penghuni', [
            'id' => $resident->id,
            'foto_ktp_path' => 'ktp/budi.jpg',
            'jenis_penghuni' => 'kontrak',
        ]);
    }

    public function test_rumah_dapat_ditambah_dan_diperbarui_tanpa_nomor_duplikat(): void
    {
        $create = $this->postJson('/api/rumah', [
            'nomor_rumah' => 'B-01',
            'alamat' => 'Blok B Nomor 1',
        ])->assertSuccessful();

        $houseId = $create->json('data.id');
        House::create(['nomor_rumah' => 'B-02']);

        $this->patchJson("/api/rumah/{$houseId}", ['alamat' => 'Alamat yang diperbarui'])
            ->assertOk()
            ->assertJsonPath('data.alamat', 'Alamat yang diperbarui');

        $this->patchJson("/api/rumah/{$houseId}", ['nomor_rumah' => 'B-02'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nomor_rumah');
    }

    public function test_daftar_penghuni_mendukung_hingga_seratus_data_untuk_pemilihan_hunian(): void
    {
        foreach (range(1, 15) as $number) {
            Resident::create([
                'nama_lengkap' => "Penghuni {$number}",
                'foto_ktp_path' => "ktp/{$number}.jpg",
                'jenis_penghuni' => 'tetap',
                'nomor_telepon' => '08'.str_pad((string) $number, 10, '0', STR_PAD_LEFT),
                'sudah_menikah' => false,
            ]);
        }

        $this->getJson('/api/penghuni?per_page=100')
            ->assertOk()
            ->assertJsonCount(15, 'data');
    }
}
