<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\PaymentResource;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function yearly(Request $request): JsonResponse
    {
        $year = (int) $request->input('tahun', now()->year);
        abort_unless($year >= 2000 && $year <= 2100, 422, 'Tahun laporan tidak valid.');

        $start = CarbonImmutable::create($year, 1, 1)->startOfDay();
        $openingBalance = (float) Payment::where('tanggal_bayar', '<', $start)->sum('total_bayar')
            - (float) Expense::where('tanggal_pengeluaran', '<', $start)->sum('nominal');

        $incomeByMonth = Payment::selectRaw('MONTH(tanggal_bayar) as bulan, SUM(total_bayar) as total')
            ->whereYear('tanggal_bayar', $year)->groupByRaw('MONTH(tanggal_bayar)')->pluck('total', 'bulan');
        $expenseByMonth = Expense::selectRaw('MONTH(tanggal_pengeluaran) as bulan, SUM(nominal) as total')
            ->whereYear('tanggal_pengeluaran', $year)->groupByRaw('MONTH(tanggal_pengeluaran)')->pluck('total', 'bulan');

        $balance = $openingBalance;
        $months = collect(range(1, 12))->map(function ($month) use (&$balance, $incomeByMonth, $expenseByMonth, $year) {
            $income = (float) ($incomeByMonth[$month] ?? 0);
            $expense = (float) ($expenseByMonth[$month] ?? 0);
            $balance += $income - $expense;

            return [
                'bulan' => CarbonImmutable::create($year, $month)->translatedFormat('F'),
                'nomor_bulan' => $month,
                'pemasukan' => $income,
                'pengeluaran' => $expense,
                'selisih' => $income - $expense,
                'saldo' => $balance,
            ];
        });

        return response()->json([
            'tahun' => $year,
            'saldo_awal' => $openingBalance,
            'total_pemasukan' => $months->sum('pemasukan'),
            'total_pengeluaran' => $months->sum('pengeluaran'),
            'saldo_akhir' => $balance,
            'bulanan' => $months,
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        $request->validate(['bulan' => ['required', 'date_format:Y-m']]);
        $month = CarbonImmutable::createFromFormat('Y-m', $request->string('bulan')->toString())->startOfMonth();
        $payments = Payment::with(['house', 'allocations.bill.dueType'])
            ->whereBetween('tanggal_bayar', [$month, $month->endOfMonth()])->orderBy('tanggal_bayar')->get();
        $expenses = Expense::whereBetween('tanggal_pengeluaran', [$month, $month->endOfMonth()])
            ->orderBy('tanggal_pengeluaran')->get();
        $openingBalance = (float) Payment::where('tanggal_bayar', '<', $month)->sum('total_bayar')
            - (float) Expense::where('tanggal_pengeluaran', '<', $month)->sum('nominal');
        $income = (float) $payments->sum('total_bayar');
        $expense = (float) $expenses->sum('nominal');

        return response()->json([
            'bulan' => $month->format('Y-m'),
            'saldo_awal' => $openingBalance,
            'total_pemasukan' => $income,
            'total_pengeluaran' => $expense,
            'saldo_akhir' => $openingBalance + $income - $expense,
            'pembayaran' => PaymentResource::collection($payments),
            'pengeluaran' => ExpenseResource::collection($expenses),
        ]);
    }
}
