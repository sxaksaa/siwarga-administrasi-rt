<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EndHouseOccupancyRequest;
use App\Http\Requests\StoreHouseOccupancyRequest;
use App\Http\Resources\HouseOccupancyResource;
use App\Models\House;
use App\Models\HouseOccupancy;
use App\Models\Resident;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HouseOccupancyController extends Controller
{
    public function store(StoreHouseOccupancyRequest $request, House $house): HouseOccupancyResource
    {
        $occupancy = DB::transaction(function () use ($request, $house) {
            $lockedHouse = House::query()->lockForUpdate()->findOrFail($house->id);
            $resident = Resident::query()->lockForUpdate()->findOrFail($request->integer('penghuni_id'));
            $startDate = CarbonImmutable::parse($request->validated('mulai_tinggal'))->toDateString();

            if ($lockedHouse->occupancies()->overlapping($startDate)->exists()) {
                throw ValidationException::withMessages([
                    'rumah' => ['Tanggal mulai bertumpang tindih dengan riwayat hunian lain pada rumah ini.'],
                ]);
            }

            if ($resident->occupancies()->overlapping($startDate)->exists()) {
                throw ValidationException::withMessages([
                    'penghuni_id' => ['Tanggal mulai bertumpang tindih dengan riwayat hunian lain milik penghuni ini.'],
                ]);
            }

            return $lockedHouse->occupancies()->create($request->validated());
        });

        return new HouseOccupancyResource($occupancy->load('resident'));
    }

    public function end(
        EndHouseOccupancyRequest $request,
        House $house,
        HouseOccupancy $occupancy,
    ): HouseOccupancyResource {
        $occupancy = DB::transaction(function () use ($request, $house, $occupancy) {
            $lockedHouse = House::query()->lockForUpdate()->findOrFail($house->id);
            $resident = Resident::query()->lockForUpdate()->findOrFail($occupancy->penghuni_id);
            $lockedOccupancy = HouseOccupancy::query()->lockForUpdate()->findOrFail($occupancy->id);

            if ($lockedOccupancy->rumah_id !== $lockedHouse->id || $lockedOccupancy->selesai_tinggal !== null) {
                throw ValidationException::withMessages([
                    'riwayat_hunian' => ['Riwayat hunian aktif tidak ditemukan pada rumah ini.'],
                ]);
            }

            $startDate = $lockedOccupancy->mulai_tinggal->toDateString();
            $endDate = CarbonImmutable::parse($request->validated('selesai_tinggal'))->toDateString();

            if ($endDate <= $startDate) {
                throw ValidationException::withMessages([
                    'selesai_tinggal' => ['Tanggal keluar harus setelah tanggal mulai tinggal.'],
                ]);
            }

            if ($lockedHouse->occupancies()
                ->whereKeyNot($lockedOccupancy->id)
                ->overlapping($startDate, $endDate)
                ->exists()) {
                throw ValidationException::withMessages([
                    'selesai_tinggal' => ['Rentang tanggal bertumpang tindih dengan riwayat hunian lain pada rumah ini.'],
                ]);
            }

            if ($resident->occupancies()
                ->whereKeyNot($lockedOccupancy->id)
                ->overlapping($startDate, $endDate)
                ->exists()) {
                throw ValidationException::withMessages([
                    'selesai_tinggal' => ['Rentang tanggal bertumpang tindih dengan riwayat hunian lain milik penghuni ini.'],
                ]);
            }

            $lockedOccupancy->update([
                'selesai_tinggal' => $endDate,
                'catatan' => $request->input('catatan', $lockedOccupancy->catatan),
            ]);

            return $lockedOccupancy;
        });

        return new HouseOccupancyResource($occupancy->fresh()->load('resident'));
    }
}
