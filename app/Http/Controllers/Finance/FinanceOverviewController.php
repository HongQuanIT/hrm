<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceDebt;
use App\Services\FinanceService;

class FinanceOverviewController extends Controller
{
    public function index(FinanceService $finance)
    {
        $summary = $finance->summary();
        $cashflow = $finance->monthlyCashflow(6);
        $cashflowMax = max(1, collect($cashflow)->flatMap(fn ($m) => [$m['income'], $m['expense']])->max() ?: 1);

        $openDebts = FinanceDebt::whereNotIn('status', ['paid', 'cancelled'])->get();
        $receivableOutstanding = $openDebts->where('type', 'receivable')->sum(fn ($d) => $d->remaining_amount);
        $payableOutstanding = $openDebts->where('type', 'payable')->sum(fn ($d) => $d->remaining_amount);

        $upcomingDebts = $openDebts
            ->sortBy(fn ($d) => $d->due_date?->timestamp ?? PHP_INT_MAX)
            ->take(6)
            ->values();

        return view('finance.overview', compact(
            'summary', 'cashflow', 'cashflowMax',
            'receivableOutstanding', 'payableOutstanding', 'upcomingDebts'
        ));
    }
}
