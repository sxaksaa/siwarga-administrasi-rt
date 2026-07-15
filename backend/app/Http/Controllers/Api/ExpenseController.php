<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = Expense::query()->latest('tanggal_pengeluaran');
        if ($bulan = request('bulan')) {
            $query->whereYear('tanggal_pengeluaran', substr($bulan, 0, 4))
                ->whereMonth('tanggal_pengeluaran', substr($bulan, 5, 2));
        }
        if ($kategori = request('kategori')) {
            $query->where('kategori', $kategori);
        }

        $perPage = min(max(request()->integer('per_page', 20), 1), 100);

        return ExpenseResource::collection($query->paginate($perPage));
    }

    public function store(StoreExpenseRequest $request): ExpenseResource
    {
        $data = $request->safe()->except('bukti');
        if ($request->hasFile('bukti')) {
            $data['bukti_path'] = $request->file('bukti')->store('bukti-pengeluaran', 'public');
        }

        return new ExpenseResource(Expense::create($data));
    }

    public function show(Expense $expense): ExpenseResource
    {
        return new ExpenseResource($expense);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $data = $request->safe()->except('bukti');
        if ($request->hasFile('bukti')) {
            $newPath = $request->file('bukti')->store('bukti-pengeluaran', 'public');
            $oldPath = $expense->bukti_path;
            $data['bukti_path'] = $newPath;
            $expense->update($data);
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        } else {
            $expense->update($data);
        }

        return new ExpenseResource($expense->fresh());
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json(['message' => 'Pengeluaran berhasil dihapus.']);
    }
}
