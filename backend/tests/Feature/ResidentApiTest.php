<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_menambah_dan_mengubah_penghuni(): void
    {
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());

        $create = $this->post('/api/penghuni', [
            'nama_lengkap' => 'Budi Santoso',
            'foto_ktp' => UploadedFile::fake()->image('ktp-budi.jpg'),
            'jenis_penghuni' => 'tetap',
            'nomor_telepon' => '081234567890',
            'sudah_menikah' => true,
        ], ['Accept' => 'application/json']);

        $create->assertSuccessful()->assertJsonPath('data.nama_lengkap', 'Budi Santoso');
        $residentId = $create->json('data.id');
        $this->assertDatabaseHas('penghuni', ['id' => $residentId, 'jenis_penghuni' => 'tetap']);

        $this->get("/api/penghuni/{$residentId}/foto-ktp")
            ->assertOk();

        $this->patchJson("/api/penghuni/{$residentId}", ['nomor_telepon' => '089999999999'])
            ->assertOk()
            ->assertJsonPath('data.nomor_telepon', '089999999999');

        $this->patchJson("/api/penghuni/{$residentId}", ['nomor_telepon' => '1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nomor_telepon');
    }

    public function test_foto_ktp_wajib_dan_harus_berupa_gambar(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/penghuni', [
            'nama_lengkap' => 'Budi Santoso',
            'jenis_penghuni' => 'tetap',
            'nomor_telepon' => '081234567890',
            'sudah_menikah' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('foto_ktp');
    }

    public function test_foto_ktp_maksimal_lima_megabyte(): void
    {
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/penghuni', [
            'nama_lengkap' => 'Budi Santoso',
            'foto_ktp' => UploadedFile::fake()->image('ktp-5mb.jpg')->size(5120),
            'jenis_penghuni' => 'tetap',
            'nomor_telepon' => '081234567890',
            'sudah_menikah' => true,
        ], ['Accept' => 'application/json'])->assertSuccessful();

        $this->post('/api/penghuni', [
            'nama_lengkap' => 'Siti Aminah',
            'foto_ktp' => UploadedFile::fake()->image('ktp-lebih-dari-5mb.jpg')->size(5121),
            'jenis_penghuni' => 'tetap',
            'nomor_telepon' => '089876543210',
            'sudah_menikah' => true,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('foto_ktp');
    }

    public function test_nomor_telepon_harus_memiliki_10_sampai_15_digit(): void
    {
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/penghuni', [
            'nama_lengkap' => 'Nomor Tidak Valid',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'jenis_penghuni' => 'tetap',
            'nomor_telepon' => '1',
            'sudah_menikah' => false,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nomor_telepon');

        $this->post('/api/penghuni', [
            'nama_lengkap' => 'Nomor Valid',
            'foto_ktp' => UploadedFile::fake()->image('ktp-valid.jpg'),
            'jenis_penghuni' => 'tetap',
            'nomor_telepon' => '+62 812-3456-7890',
            'sudah_menikah' => false,
        ], ['Accept' => 'application/json'])->assertSuccessful();
    }
}
