<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateBillsRequest;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use App\Models\DueType;
use App\Models\House;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = Bill::query()->with(['house', 'dueType'])->latest('periode_tagihan');

        if ($periode = request('periode')) {
            $query->whereDate('periode_tagihan', CarbonImmutable::createFromFormat('Y-m', $periode)->startOfMonth());
        }
        if ($status = request('status')) {
            $query->where('status', $status);
        }
        if ($rumahId = request('rumah_id')) {
            $query->where('rumah_id', $rumahId);
        }

        $perPage = min(max(request()->integer('per_page', 20), 1), 100);

        return BillResource::collection($query->paginate($perPage));
    }

    public function generate(GenerateBillsRequest $request): JsonResponse
    {
        $period = CarbonImmutable::createFromFormat('Y-m', $request->string('periode')->toString())->startOfMonth();
        $dueDate = $request->filled('jatuh_tempo')
            ? CarbonImmutable::parse($request->input('jatuh_tempo'))->toDateString()
            : $period->day(10)->toDateString();

        $result = DB::transaction(function () use ($period, $dueDate) {
            $types = DueType::where('aktif', true)->get();
            $created = 0;
            $skipped = 0;

            House::query()->with(['occupancies' => function ($query) use ($period) {
                $query->with('resident')
                    ->whereDate('mulai_tinggal', '<=', $period->endOfMonth())
                    ->where(fn ($builder) => $builder->whereNull('selesai_tinggal')
                        ->orWhereDate('selesai_tinggal', '>', $period))
                    ->oldest('mulai_tinggal');
            }])->chunkById(50, function ($houses) use ($types, $period, $dueDate, &$created, &$skipped) {
                foreach ($houses as $house) {
                    $occupancy = $house->occupancies->first(fn ($item) => $item->mulai_tinggal->lessThanOrEqualTo($period)
                        && ($item->selesai_tinggal === null || $item->selesai_tinggal->greaterThan($period)))
                        ?? $house->occupancies->first();
                    if (! $occupancy) {
                        continue;
                    }

                    foreach ($types as $type) {
                        $bill = Bill::firstOrCreate(
                            [
                                'rumah_id' => $house->id,
                                'jenis_iuran_id' => $type->id,
                                'periode_tagihan' => $period->toDateString(),
                            ],
                            [
                                'penghuni_id' => $occupancy->penghuni_id,
                                'nominal' => $type->nominal_default,
                                'jatuh_tempo' => $dueDate,
                                'nama_penghuni_snapshot' => $occupancy->resident->nama_lengkap,
                                'jenis_penghuni_snapshot' => $occupancy->resident->jenis_penghuni,
                            ],
                        );
                        $bill->wasRecentlyCreated ? $created++ : $skipped++;
                    }
                }
            });

            return ['dibuat' => $created, 'dilewati_karena_sudah_ada' => $skipped];
        });

        return response()->json([
            'message' => 'Pembuatan tagihan selesai.',
            'periode' => $period->format('Y-m'),
            ...$result,
        ]);
    }
}
