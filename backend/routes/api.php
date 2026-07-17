<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\HouseController;
use App\Http\Controllers\Api\HouseOccupancyController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ResidentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/pengguna', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('penghuni', ResidentController::class)
        ->parameters(['penghuni' => 'resident'])
        ->except('destroy');
    Route::get('/penghuni/{resident}/foto-ktp', [ResidentController::class, 'photo']);

    Route::apiResource('rumah', HouseController::class)
        ->parameters(['rumah' => 'house'])
        ->except('destroy');

    Route::post('/rumah/{house}/hunian', [HouseOccupancyController::class, 'store']);
    Route::patch('/rumah/{house}/hunian/{occupancy}/selesai', [HouseOccupancyController::class, 'end']);

    Route::get('/tagihan', [BillController::class, 'index']);
    Route::post('/tagihan/buat-bulanan', [BillController::class, 'generate']);
    Route::get('/pembayaran', [PaymentController::class, 'index']);
    Route::get('/pembayaran/opsi', [PaymentController::class, 'options']);
    Route::post('/pembayaran/siapkan-tagihan', [PaymentController::class, 'prepareBills']);
    Route::post('/pembayaran', [PaymentController::class, 'store']);
    Route::apiResource('pengeluaran', ExpenseController::class)
        ->parameters(['pengeluaran' => 'expense']);
    Route::get('/laporan/tahunan', [ReportController::class, 'yearly']);
    Route::get('/laporan/bulanan', [ReportController::class, 'monthly']);
});
