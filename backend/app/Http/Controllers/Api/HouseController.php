<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHouseRequest;
use App\Http\Requests\UpdateHouseRequest;
use App\Http\Resources\HouseResource;
use App\Models\House;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HouseController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = House::query()->with('activeOccupancy.resident')->orderBy('nomor_rumah');

        if ($search = request()->string('cari')->trim()->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('nomor_rumah', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        if (request('status') === 'dihuni') {
            $query->whereHas('activeOccupancy');
        } elseif (request('status') === 'tidak_dihuni') {
            $query->whereDoesntHave('activeOccupancy');
        }

        $perPage = min(max(request()->integer('per_page', 20), 1), 100);

        return HouseResource::collection($query->paginate($perPage));
    }

    public function store(StoreHouseRequest $request): HouseResource
    {
        return new HouseResource(House::create($request->validated()));
    }

    public function show(House $house): HouseResource
    {
        $house->load([
            'activeOccupancy.resident',
            'occupancies' => fn ($query) => $query->with('resident')->latest('mulai_tinggal'),
            'bills' => fn ($query) => $query->with('dueType')->latest('periode_tagihan')->latest('id'),
        ]);

        return new HouseResource($house);
    }

    public function update(UpdateHouseRequest $request, House $house): HouseResource
    {
        $house->update($request->validated());

        return new HouseResource($house->fresh()->load('activeOccupancy.resident'));
    }
}
