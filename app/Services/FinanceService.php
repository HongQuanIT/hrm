<?php

namespace App\Services;

use App\Models\FinanceAccount;
use App\Models\FinanceDebt;
use App\Models\FinanceTransaction;
use Carbon\Carbon;

class FinanceService
{
    /**
     * Tập chỉ số tài chính tổng hợp (dùng cho trang tổng quan & Dashboard).
     *
     * Quy ước (BR-M10-02..07): mọi con số tính trên các quỹ đang hoạt động, để
     * giữ đẳng thức: Số dư hiện tại = Tổng nạp + Tổng thu khác − Tổng chi.
     *
     * @return array{contributed: float, other_income: float, spent: float, balance: float}
     */
    public function summary(): array
    {
        $activeIds = FinanceAccount::where('is_active', true)->pluck('id');

        $openingSum = (float) FinanceAccount::where('is_active', true)->sum('opening_balance');

        $income = FinanceTransaction::whereIn('account_id', $activeIds)
            ->where('direction', 'income')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_contribution = 1 THEN amount ELSE 0 END), 0) as contrib')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_contribution = 0 THEN amount ELSE 0 END), 0) as other')
            ->first();

        $spent = (float) FinanceTransaction::whereIn('account_id', $activeIds)
            ->where('direction', 'expense')
            ->sum('amount');

        // "Tổng đã nạp" gồm số dư đầu kỳ (nạp ban đầu) + các giao dịch nạp vốn.
        $contributed = $openingSum + (float) ($income->contrib ?? 0);
        $otherIncome = (float) ($income->other ?? 0);

        return [
            'contributed' => $contributed,
            'other_income' => $otherIncome,
            'spent' => $spent,
            'balance' => $contributed + $otherIncome - $spent,
        ];
    }

    /**
     * Dòng tiền theo tháng (thu/nạp vs chi) trong N tháng gần nhất.
     * Mỗi tháng một truy vấn gộp thu & chi (portable MySQL/SQLite).
     *
     * @return array<int, array{label: string, income: float, expense: float}>
     */
    public function monthlyCashflow(int $months = 6): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $result = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $row = FinanceTransaction::whereYear('occurred_on', $month->year)
                ->whereMonth('occurred_on', $month->month)
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'income' THEN amount ELSE 0 END), 0) as income")
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'expense' THEN amount ELSE 0 END), 0) as expense")
                ->first();

            $result[] = [
                'label' => 'Th' . $month->format('m'),
                'income' => (float) ($row->income ?? 0),
                'expense' => (float) ($row->expense ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Tính lại trạng thái công nợ dựa trên số đã thanh toán và hạn (BR-M10-11).
     */
    public function refreshDebtStatus(FinanceDebt $debt): void
    {
        if ($debt->status === 'cancelled') {
            return;
        }

        $paid = $debt->paid_amount;
        $total = (float) $debt->amount;

        if ($paid >= $total && $total > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'open';
        }

        // Quá hạn nếu chưa trả đủ và đã qua hạn.
        if ($status !== 'paid' && $debt->due_date && $debt->due_date->lt(Carbon::today())) {
            $status = 'overdue';
        }

        if ($debt->status !== $status) {
            $debt->update(['status' => $status]);
        }
    }
}
