<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\PaymentResource;
use App\Models\Bill;
use App\Models\HouseOccupancy;
use App\Models\Payment;
use App\Models\Resident;
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
            ->whereIn('status', ['belum_lunas', 'sebagian'])
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
                'metode_pembayaran' => $request->input('metode_pembayaran'),
                'catatan' => $request->input('catatan'),
            ]);

            foreach ($allocations as $allocation) {
                $bill = $bills->get($allocation['tagihan_id']);
                $amountCents = $this->toCents($allocation['nominal']);
                $newPaidCents = $this->toCents($bill->nominal_terbayar) + $amountCents;
                $billCents = $this->toCents($bill->nominal);

                $payment->allocations()->create(['tagihan_id' => $bill->id, 'nominal' => $amountCents / 100]);
                $bill->update([
                    'nominal_terbayar' => $newPaidCents / 100,
                    'status' => $newPaidCents >= $billCents ? 'lunas' : 'sebagian',
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
