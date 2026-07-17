<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\DueType;
use App\Models\Expense;
use App\Models\House;
use App\Models\HouseOccupancy;
use App\Models\Payment;
use App\Models\Resident;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    /**
     * Mengisi data fiktif untuk demonstrasi dan screenshot.
     * Seeder ini sengaja tidak dipanggil oleh DatabaseSeeder.
     */
    public function run(): void
    {
        $this->call([AdminUserSeeder::class, MasterDataSeeder::class]);

        DB::transaction(function () {
            $residents = $this->seedResidents();
            $this->seedOccupancies($residents);
            $this->seedBillsAndPayments();
            $this->seedExpenses();
        });
    }

    /** @return array<int, Resident> */
    private function seedResidents(): array
    {
        $demoPhoto = file_get_contents(database_path('seeders/assets/ktp-demo-kucing.png'));
        if ($demoPhoto === false) {
            throw new \RuntimeException('Aset foto KTP demo tidak ditemukan.');
        }

        $names = [
            'Andi Pratama', 'Budi Santoso', 'Citra Lestari', 'Dedi Kurniawan', 'Eka Wulandari',
            'Fajar Ramadhan', 'Gita Permata', 'Hendra Wijaya', 'Indah Puspitasari', 'Joko Saputra',
            'Kartika Sari', 'Lukman Hakim', 'Maya Anggraini', 'Nanda Putri', 'Oki Setiawan',
            'Putri Maharani', 'Raka Wijaya', 'Sinta Amelia',
        ];

        return collect($names)->map(function ($name, $index) use ($demoPhoto) {
            $number = $index + 1;

            $resident = Resident::updateOrCreate(
                ['nomor_telepon' => '0812'.str_pad((string) $number, 8, '0', STR_PAD_LEFT)],
                [
                    'nama_lengkap' => $name,
                    'jenis_penghuni' => $number > 15 ? 'kontrak' : 'tetap',
                    'sudah_menikah' => $number % 3 !== 0,
                ],
            );

            $photoPath = "ktp/demo-penghuni-{$resident->id}.png";
            Storage::disk('local')->put($photoPath, $demoPhoto);
            $resident->update(['foto_ktp_path' => $photoPath]);

            return $resident;
        })->all();
    }

    /** @param array<int, Resident> $residents */
    private function seedOccupancies(array $residents): void
    {
        foreach ($residents as $index => $resident) {
            $house = House::where('nomor_rumah', sprintf('A-%02d', $index + 1))->firstOrFail();

            HouseOccupancy::updateOrCreate(
                ['rumah_id' => $house->id, 'penghuni_id' => $resident->id, 'selesai_tinggal' => null],
                [
                    'mulai_tinggal' => $index < 10 ? '2025-01-01' : '2026-01-01',
                    'catatan' => 'Data fiktif untuk demonstrasi aplikasi.',
                ],
            );
        }
    }

    private function seedBillsAndPayments(): void
    {
        $dueTypes = DueType::where('aktif', true)->get();
        $houses = House::with('activeOccupancy.resident')
            ->whereHas('activeOccupancy')
            ->orderBy('nomor_rumah')
            ->get();

        foreach (range(1, 7) as $monthNumber) {
            $period = CarbonImmutable::create(2026, $monthNumber, 1);

            foreach ($houses as $houseIndex => $house) {
                $occupancy = $house->activeOccupancy;
                if (! $occupancy) {
                    continue;
                }

                $bills = $dueTypes->map(fn ($type) => Bill::firstOrCreate(
                    [
                        'rumah_id' => $house->id,
                        'jenis_iuran_id' => $type->id,
                        'periode_tagihan' => $period->toDateString(),
                    ],
                    [
                        'penghuni_id' => $occupancy->penghuni_id,
                        'nominal' => $type->nominal_default,
                        'jatuh_tempo' => $period->day(10)->toDateString(),
                        'nama_penghuni_snapshot' => $occupancy->resident->nama_lengkap,
                        'jenis_penghuni_snapshot' => $occupancy->resident->jenis_penghuni,
                    ],
                ));

                $shouldPay = $monthNumber <= 5 || ($monthNumber === 6 && $houseIndex < 13) || ($monthNumber === 7 && $houseIndex < 5);

                if ($shouldPay) {
                    $this->seedPayment($house, $occupancy->resident, $period, $bills->all());
                }
            }
        }
    }

    /** @param array<int, Bill> $bills */
    private function seedPayment(House $house, Resident $resident, CarbonImmutable $period, array $bills): void
    {
        $allocations = collect($bills)->map(fn ($bill) => [$bill, (float) $bill->nominal]);
        $total = $allocations->sum(fn ($row) => $row[1]);

        $payment = Payment::updateOrCreate(
            ['nomor_bukti' => 'DEMO-'.$period->format('Ym').'-'.$house->nomor_rumah],
            [
                'rumah_id' => $house->id,
                'penghuni_id' => $resident->id,
                'tanggal_bayar' => $period->day(5)->setTime(9, 0),
                'total_bayar' => $total,
                'nama_pembayar_snapshot' => $resident->nama_lengkap,
                'catatan' => 'Pembayaran fiktif untuk demonstrasi.',
            ],
        );

        foreach ($allocations as [$bill, $amount]) {
            $payment->allocations()->updateOrCreate(['tagihan_id' => $bill->id], ['nominal' => $amount]);
            $bill->update([
                'nominal_terbayar' => $amount,
                'status' => 'lunas',
            ]);
        }
    }

    private function seedExpenses(): void
    {
        foreach (range(1, 7) as $monthNumber) {
            $date = CarbonImmutable::create(2026, $monthNumber, 7)->toDateString();
            Expense::updateOrCreate(
                ['keterangan' => 'Gaji satpam bulan '.CarbonImmutable::create(2026, $monthNumber)->translatedFormat('F Y')],
                ['kategori' => 'Keamanan', 'nominal' => 1200000, 'tanggal_pengeluaran' => $date, 'rutin' => true],
            );
            Expense::updateOrCreate(
                ['keterangan' => 'Token listrik pos satpam bulan '.CarbonImmutable::create(2026, $monthNumber)->translatedFormat('F Y')],
                ['kategori' => 'Operasional', 'nominal' => 150000, 'tanggal_pengeluaran' => $date, 'rutin' => true],
            );
        }

        Expense::updateOrCreate(
            ['keterangan' => 'Perbaikan jalan blok A'],
            ['kategori' => 'Perbaikan', 'nominal' => 600000, 'tanggal_pengeluaran' => '2026-03-18', 'rutin' => false],
        );
        Expense::updateOrCreate(
            ['keterangan' => 'Pembersihan dan perbaikan selokan'],
            ['kategori' => 'Perbaikan', 'nominal' => 450000, 'tanggal_pengeluaran' => '2026-06-21', 'rutin' => false],
        );
    }
}
