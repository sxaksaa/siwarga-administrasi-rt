<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\PaymentResource;
use App\Models\Bill;
use App\Models\DueType;
use App\Models\HouseOccupancy;
use App\Models\Payment;
use App\Models\Resident;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = Payment::query()->with(['house', 'allocations.bill.dueType'])->latest('tanggal_bayar');
        if ($bulan = request('bulan')) {
            $query->whereYear('tanggal_bayar', substr($bulan, 0, 4))->whereMonth('tanggal_bayar', substr($bulan, 5, 2));
        }
        if ($rumahId = request('rumah_id')) {
            $query->where('rumah_id', $rumahId);
        }

        $perPage = min(max(request()->integer('per_page', 20), 1), 100);

        return PaymentResource::collection($query->paginate($perPage));
    }

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rumah_id' => ['required', 'integer', 'exists:rumah,id'],
        ], [
            'rumah_id.required' => 'Rumah wajib dipilih.',
            'rumah_id.exists' => 'Data rumah tidak ditemukan.',
        ]);

        $houseId = (int) $validated['rumah_id'];
        $bills = Bill::query()
            ->with(['house', 'dueType'])
            ->where('rumah_id', $houseId)
            ->where('status', 'belum_lunas')
            ->orderBy('periode_tagihan')
            ->orderBy('jenis_iuran_id')
            ->get();

        $activeResidentId = HouseOccupancy::query()
            ->where('rumah_id', $houseId)
            ->whereNull('selesai_tinggal')
            ->latest('mulai_tinggal')
            ->value('penghuni_id');

        $residentIds = $bills->pluck('penghuni_id')
            ->filter()
            ->when($activeResidentId, fn ($ids) => $ids->push($activeResidentId))
            ->unique()
            ->values();

        $residents = Resident::query()
            ->whereIn('id', $residentIds)
            ->orderBy('nama_lengkap')
            ->get()
            ->map(fn (Resident $resident) => [
                'id' => $resident->id,
                'nama_lengkap' => $resident->nama_lengkap,
                'penghuni_aktif' => $resident->id === (int) $activeResidentId,
                'tagihan_ids' => $bills->where('penghuni_id', $resident->id)->pluck('id')->values(),
            ])
            ->values();

        return response()->json([
            'data' => [
                // Endpoint ini sengaja tidak dipaginasi karena hanya memuat tagihan tertunggak satu rumah.
                'tagihan' => $bills->map(fn (Bill $bill) => (new BillResource($bill))->resolve($request))->values(),
                'pembayar' => $residents,
            ],
        ]);
    }

    public function prepareBills(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rumah_id' => ['required', 'integer', 'exists:rumah,id'],
            'periode_awal' => ['required', 'date_format:Y-m'],
            'durasi' => ['required', 'integer', 'in:1,12'],
        ], [
            'rumah_id.required' => 'Rumah wajib dipilih.',
            'periode_awal.required' => 'Periode awal wajib dipilih.',
            'durasi.in' => 'Durasi pembayaran harus bulanan atau tahunan.',
        ]);

        $occupancy = HouseOccupancy::query()
            ->with('resident')
            ->where('rumah_id', $validated['rumah_id'])
            ->whereNull('selesai_tinggal')
            ->latest('mulai_tinggal')
            ->first();

        if (! $occupancy) {
            throw ValidationException::withMessages([
                'rumah_id' => ['Rumah harus memiliki penghuni aktif untuk menyiapkan tagihan.'],
            ]);
        }

        $startPeriod = CarbonImmutable::createFromFormat('Y-m', $validated['periode_awal'])->startOfMonth();
        if ($startPeriod->endOfMonth()->isBefore($occupancy->mulai_tinggal)) {
            throw ValidationException::withMessages([
                'periode_awal' => ['Periode awal tidak boleh sebelum penghuni mulai menempati rumah.'],
            ]);
        }

        $duration = (int) $validated['durasi'];
        $types = DueType::query()->where('aktif', true)->get();
        $billIds = [];
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($occupancy, $startPeriod, $duration, $types, &$billIds, &$created, &$skipped) {
            foreach ($types as $type) {
                $months = $duration === 12 && $type->kode !== 'SATPAM' ? 12 : 1;

                foreach (range(0, $months - 1) as $offset) {
                    $period = $startPeriod->addMonths($offset);
                    $bill = Bill::firstOrCreate(
                        [
                            'rumah_id' => $occupancy->rumah_id,
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
                    );

                    $bill->wasRecentlyCreated ? $created++ : $skipped++;
                    $billIds[] = $bill->id;
                }
            }
        });

        return response()->json([
            'message' => $duration === 12
                ? 'Tagihan tahunan berhasil disiapkan. Iuran satpam hanya disiapkan untuk bulan pertama.'
                : 'Tagihan bulanan berhasil disiapkan.',
            'data' => [
                'tagihan_ids' => $billIds,
                'dibuat' => $created,
                'sudah_tersedia' => $skipped,
            ],
        ]);
    }

    public function store(StorePaymentRequest $request): PaymentResource
    {
        $payment = DB::transaction(function () use ($request) {
            $allocations = collect($request->validated('alokasi'));
            $bills = Bill::query()->whereIn('id', $allocations->pluck('tagihan_id')->sort()->values())
                ->lockForUpdate()->get()->keyBy('id');

            $totalCents = 0;
            foreach ($allocations as $allocation) {
                $bill = $bills->get($allocation['tagihan_id']);
                if (! $bill || $bill->rumah_id !== $request->integer('rumah_id')) {
                    throw ValidationException::withMessages(['alokasi' => ['Semua tagihan harus berasal dari rumah yang dipilih.']]);
                }

                $allocationCents = $this->toCents($allocation['nominal']);
                $remainingCents = $this->toCents($bill->nominal) - $this->toCents($bill->nominal_terbayar);
                if ($allocationCents > $remainingCents) {
                    throw ValidationException::withMessages([
                        'alokasi' => ["Pembayaran tagihan #{$bill->id} melebihi sisa tagihan."],
                    ]);
                }
                if ($allocationCents !== $remainingCents) {
                    throw ValidationException::withMessages([
                        'alokasi' => ["Tagihan #{$bill->id} harus dibayar lunas sebesar ".number_format($remainingCents / 100, 0, ',', '.').'.'],
                    ]);
                }
                $totalCents += $allocationCents;
            }

            $resident = Resident::findOrFail($request->integer('penghuni_id'));
            $isActiveResident = HouseOccupancy::query()
                ->where('rumah_id', $request->integer('rumah_id'))
                ->where('penghuni_id', $resident->id)
                ->whereNull('selesai_tinggal')
                ->exists();
            $isBilledResident = $bills->contains(
                fn (Bill $bill) => $bill->penghuni_id === $resident->id,
            );

            if (! $isActiveResident && ! $isBilledResident) {
                throw ValidationException::withMessages([
                    'penghuni_id' => ['Pembayar harus merupakan penghuni aktif rumah atau penghuni yang tercatat pada tagihan terpilih.'],
                ]);
            }

            $payment = Payment::create([
                'nomor_bukti' => 'BYR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'rumah_id' => $request->integer('rumah_id'),
                'penghuni_id' => $resident?->id,
                'tanggal_bayar' => $request->date('tanggal_bayar'),
                'total_bayar' => $totalCents / 100,
                'nama_pembayar_snapshot' => $resident->nama_lengkap,
                'catatan' => $request->input('catatan'),
            ]);

            foreach ($allocations as $allocation) {
                $bill = $bills->get($allocation['tagihan_id']);
                $amountCents = $this->toCents($allocation['nominal']);
                $newPaidCents = $this->toCents($bill->nominal_terbayar) + $amountCents;

                $payment->allocations()->create(['tagihan_id' => $bill->id, 'nominal' => $amountCents / 100]);
                $bill->update([
                    'nominal_terbayar' => $newPaidCents / 100,
                    'status' => 'lunas',
                ]);
            }

            return $payment;
        });

        return new PaymentResource($payment->load(['house', 'allocations.bill.dueType']));
    }

    private function toCents(int|float|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
