<?php

namespace Database\Seeders;

use App\Models\DueType;
use App\Models\House;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DueType::updateOrCreate(
            ['kode' => 'SATPAM'],
            ['nama' => 'Iuran Satpam', 'nominal_default' => 100000, 'aktif' => true],
        );
        DueType::updateOrCreate(
            ['kode' => 'KEBERSIHAN'],
            ['nama' => 'Iuran Kebersihan', 'nominal_default' => 15000, 'aktif' => true],
        );

        foreach (range(1, 20) as $number) {
            House::firstOrCreate(
                ['nomor_rumah' => sprintf('A-%02d', $number)],
                ['alamat' => 'Perumahan Elite Blok A'],
            );
        }
    }
}
