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
            'Putri Maharani', 'Raka Wijaya', 'Sinta Amelia', 'Atmint Loh',
        ];

        return collect($names)->map(function ($name, $index) use ($demoPhoto) {
            $number = $index + 1;
            $isReplacementResident = $name === 'Atmint Loh';

            $resident = Resident::updateOrCreate(
                ['nomor_telepon' => $isReplacementResident
                    ? '089876538274'
                    : '0812'.str_pad((string) $number, 8, '0', STR_PAD_LEFT)],
                [
                    'nama_lengkap' => $name,
                    'jenis_penghuni' => $number > 15 ? 'kontrak' : 'tetap',
                    'sudah_menikah' => $isReplacementResident ? false : $number % 3 !== 0,
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
        foreach (array_slice($residents, 0, 18) as $index => $resident) {
            $house = House::where('nomor_rumah', sprintf('A-%02d', $index + 1))->firstOrFail();
            $startDate = $index < 10 ? '2025-01-01' : '2026-01-01';

            if ($resident->nama_lengkap === 'Raka Wijaya') {
                $this->seedHistoricalOccupancy($house, $resident, $startDate, '2026-07-17');

                continue;
            }

            $this->seedActiveOccupancy($house, $resident, $startDate);
        }

        $replacementResident = collect($residents)->firstWhere('nama_lengkap', 'Atmint Loh');
        $replacementHouse = House::where('nomor_rumah', 'A-17')->firstOrFail();

        $this->seedActiveOccupancy($replacementHouse, $replacementResident, '2026-07-17');
    }

    private function seedHistoricalOccupancy(House $house, Resident $resident, string $startDate, string $endDate): void
    {
        $demoOccupancies = HouseOccupancy::query()
            ->where('rumah_id', $house->id)
            ->where('penghuni_id', $resident->id)
            ->whereDate('mulai_tinggal', $startDate);

        if ((clone $demoOccupancies)->whereNotNull('selesai_tinggal')->exists()) {
            (clone $demoOccupancies)->whereNull('selesai_tinggal')->delete();

            return;
        }

        $activeOccupancy = (clone $demoOccupancies)->whereNull('selesai_tinggal')->first();
        if ($activeOccupancy) {
            $activeOccupancy->update(['selesai_tinggal' => $endDate]);

            return;
        }

        HouseOccupancy::create([
            'rumah_id' => $house->id,
            'penghuni_id' => $resident->id,
            'mulai_tinggal' => $startDate,
            'selesai_tinggal' => $endDate,
            'catatan' => 'Data fiktif untuk demonstrasi aplikasi.',
        ]);
    }

    private function seedActiveOccupancy(House $house, Resident $resident, string $startDate): void
    {
        $demoOccupancies = HouseOccupancy::query()
            ->where('rumah_id', $house->id)
            ->where('penghuni_id', $resident->id)
            ->whereDate('mulai_tinggal', $startDate);

        // Jangan mengaktifkan kembali penghuni demo yang sudah selesai.
        if ((clone $demoOccupancies)->whereNotNull('selesai_tinggal')->exists()) {
            (clone $demoOccupancies)->whereNull('selesai_tinggal')->delete();

            return;
        }

        // Pertahankan pergantian penghuni yang dibuat melalui aplikasi.
        if (HouseOccupancy::query()->where('rumah_id', $house->id)->whereNull('selesai_tinggal')->exists()
            || HouseOccupancy::query()->where('penghuni_id', $resident->id)->whereNull('selesai_tinggal')->exists()) {
            return;
        }

        HouseOccupancy::create([
            'rumah_id' => $house->id,
            'penghuni_id' => $resident->id,
            'mulai_tinggal' => $startDate,
            'catatan' => 'Data fiktif untuk demonstrasi aplikasi.',
        ]);
    }

    private function seedBillsAndPayments(): void
    {
        $dueTypes = DueType::where('aktif', true)->get();
        $houses = House::orderBy('nomor_rumah')->get();

        foreach (range(1, 7) as $monthNumber) {
            $period = CarbonImmutable::create(2026, $monthNumber, 1);

            foreach ($houses as $houseIndex => $house) {
                $occupancy = HouseOccupancy::query()
                    ->with('resident')
                    ->where('rumah_id', $house->id)
                    ->whereDate('mulai_tinggal', '<=', $period->toDateString())
                    ->where(fn ($query) => $query
                        ->whereNull('selesai_tinggal')
                        ->orWhereDate('selesai_tinggal', '>', $period->toDateString()))
                    ->latest('mulai_tinggal')
                    ->first();

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

        $this->seedAnnualCleaningBills();
        $this->seedReplacementResidentPayment();
    }

    private function seedAnnualCleaningBills(): void
    {
        $house = House::where('nomor_rumah', 'A-17')->firstOrFail();
        $resident = Resident::where('nama_lengkap', 'Atmint Loh')->firstOrFail();
        $cleaningDue = DueType::where('kode', 'KEBERSIHAN')->firstOrFail();

        foreach (range(1, 11) as $monthOffset) {
            $period = CarbonImmutable::create(2026, 7, 1)->addMonths($monthOffset);

            Bill::firstOrCreate(
                [
                    'rumah_id' => $house->id,
                    'jenis_iuran_id' => $cleaningDue->id,
                    'periode_tagihan' => $period->toDateString(),
                ],
                [
                    'penghuni_id' => $resident->id,
                    'nominal' => $cleaningDue->nominal_default,
                    'jatuh_tempo' => $period->day(10)->toDateString(),
                    'nama_penghuni_snapshot' => $resident->nama_lengkap,
                    'jenis_penghuni_snapshot' => $resident->jenis_penghuni,
                ],
            );
        }
    }

    private function seedReplacementResidentPayment(): void
    {
        $house = House::where('nomor_rumah', 'A-17')->firstOrFail();
        $resident = Resident::where('nama_lengkap', 'Atmint Loh')->firstOrFail();
        $bills = Bill::query()
            ->where('rumah_id', $house->id)
            ->whereDate('periode_tagihan', '2026-06-01')
            ->get();
        $total = (float) $bills->sum('nominal');

        $payment = Payment::firstOrCreate(
            ['nomor_bukti' => 'BYR-20260717-KQ4GVI'],
            [
                'rumah_id' => $house->id,
                'penghuni_id' => $resident->id,
                'tanggal_bayar' => CarbonImmutable::create(2026, 7, 17),
                'total_bayar' => $total,
                'nama_pembayar_snapshot' => $resident->nama_lengkap,
            ],
        );

        foreach ($bills as $bill) {
            $payment->allocations()->updateOrCreate(
                ['tagihan_id' => $bill->id],
                ['nominal' => $bill->nominal],
            );
            $bill->update([
                'nominal_terbayar' => $bill->nominal,
                'status' => 'lunas',
            ]);
        }
    }

    /** @param array<int, Bill> $bills */
    private function seedPayment(House $house, Resident $resident, CarbonImmutable $period, array $bills): void
    {
        $allocations = collect($bills)->map(fn ($bill) => [$bill, (float) $bill->nominal]);
        $total = $allocations->sum(fn ($row) => $row[1]);

        $paymentDate = $period->day(5)->setTime(9, 0);
        $proofNumber = 'BYR-'.$paymentDate->format('Ymd').'-'.$house->nomor_rumah;
        $legacyProofNumber = 'DEMO-'.$period->format('Ym').'-'.$house->nomor_rumah;
        $payment = Payment::whereIn('nomor_bukti', [$proofNumber, $legacyProofNumber])->first();

        $attributes = [
            'nomor_bukti' => $proofNumber,
            'rumah_id' => $house->id,
            'penghuni_id' => $resident->id,
            'tanggal_bayar' => $paymentDate,
            'total_bayar' => $total,
            'nama_pembayar_snapshot' => $resident->nama_lengkap,
            'catatan' => 'Pembayaran fiktif untuk demonstrasi.',
        ];

        if (! $payment) {
            $payment = Payment::create($attributes);
        } elseif ($payment->nomor_bukti === $legacyProofNumber
            || $payment->catatan === 'Pembayaran fiktif untuk demonstrasi.') {
            $payment->update($attributes);
        }

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
