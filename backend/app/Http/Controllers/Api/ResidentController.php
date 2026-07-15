<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResidentRequest;
use App\Http\Requests\UpdateResidentRequest;
use App\Http\Resources\ResidentResource;
use App\Models\Resident;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResidentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = Resident::query()->latest();

        if ($search = request()->string('cari')->trim()->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nomor_telepon', 'like', "%{$search}%");
            });
        }

        if ($jenis = request('jenis_penghuni')) {
            $query->where('jenis_penghuni', $jenis);
        }

        $perPage = min(max(request()->integer('per_page', 10), 1), 100);

        return ResidentResource::collection($query->paginate($perPage));
    }

    public function store(StoreResidentRequest $request): ResidentResource
    {
        $data = $request->safe()->except('foto_ktp');
        $data['foto_ktp_path'] = $request->file('foto_ktp')->store('ktp', 'local');

        return new ResidentResource(Resident::create($data));
    }

    public function show(Resident $resident): ResidentResource
    {
        return new ResidentResource($resident);
    }

    public function update(UpdateResidentRequest $request, Resident $resident): ResidentResource
    {
        $data = $request->safe()->except('foto_ktp');

        if ($request->hasFile('foto_ktp')) {
            $newPath = $request->file('foto_ktp')->store('ktp', 'local');
            $oldPath = $resident->foto_ktp_path;
            $data['foto_ktp_path'] = $newPath;
            $resident->update($data);
            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
        } else {
            $resident->update($data);
        }

        return new ResidentResource($resident->fresh());
    }

    public function photo(Resident $resident): StreamedResponse
    {
        abort_unless(
            $resident->foto_ktp_path && Storage::disk('local')->exists($resident->foto_ktp_path),
            404,
            'Foto KTP tidak ditemukan.',
        );

        return Storage::disk('local')->response($resident->foto_ktp_path);
    }
}
