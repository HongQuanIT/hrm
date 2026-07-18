<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayPayrollRequest;
use App\Http\Requests\StorePayrollPeriodRequest;
use App\Models\FinanceAccount;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;

class PayrollPeriodController extends Controller
{
    public function index()
    {
        $periods = PayrollPeriod::withCount('payslips')
            ->withSum('payslips as net_total', 'net_amount')
            ->orderByDesc('year')->orderByDesc('month')
            ->paginate(15);

        return view('payroll.index', compact('periods'));
    }

    public function store(StorePayrollPeriodRequest $request, PayrollService $payroll)
    {
        $data = $request->validated();
        $month = (int) $data['month'];
        $year = (int) $data['year'];

        $period = PayrollPeriod::create([
            'month' => $month,
            'year' => $year,
            'days_in_month' => $data['days_in_month'] ?? $payroll->daysInMonth($year, $month),
            'status' => 'draft',
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('payroll.periods.show', $period)
            ->with('status', 'Đã tạo kỳ ' . $period->label . '. Bấm "Tính lương" để lập phiếu.');
    }

    public function show(PayrollPeriod $period)
    {
        $period->load(['payslips.employee', 'payslips.items']);
        $period->setRelation('payslips', $period->payslips->sortBy(fn ($p) => $p->employee?->name)->values());
        $accounts = FinanceAccount::where('is_active', true)->orderBy('name')->get();

        return view('payroll.show', compact('period', 'accounts'));
    }

    public function calculate(PayrollPeriod $period, PayrollService $payroll)
    {
        if ($period->is_locked) {
            return back()->with('error', 'Kỳ đã duyệt/đã chi nên không thể tính lại.');
        }

        $count = $payroll->calculatePeriod($period);
        $period->update(['status' => 'calculated']);

        return back()->with('status', "Đã tính lương cho {$count} nhân viên.");
    }

    public function approve(PayrollPeriod $period)
    {
        if ($period->status !== 'calculated') {
            return back()->with('error', 'Chỉ duyệt được kỳ đã tính lương.');
        }

        $period->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Đã duyệt kỳ lương. Phiếu lương đã được khoá.');
    }

    public function reopen(PayrollPeriod $period)
    {
        if ($period->status !== 'approved') {
            return back()->with('error', 'Chỉ mở lại được kỳ đang ở trạng thái đã duyệt (chưa chi).');
        }

        $period->update(['status' => 'calculated', 'approved_by' => null, 'approved_at' => null]);

        return back()->with('status', 'Đã mở lại kỳ lương để chỉnh sửa.');
    }

    public function pay(PayPayrollRequest $request, PayrollPeriod $period, PayrollService $payroll)
    {
        if ($period->status !== 'approved') {
            return back()->with('error', 'Chỉ chi lương cho kỳ đã duyệt.');
        }

        if ($period->payslips()->sum('net_amount') <= 0) {
            return back()->with('error', 'Tổng thực nhận bằng 0, không có gì để chi.');
        }

        $data = $request->validated();
        $payroll->payViaFinance($period, (int) $data['account_id'], $data['occurred_on']);

        return back()->with('status', 'Đã chi lương và ghi nhận giao dịch vào Tài chính.');
    }

    public function destroy(PayrollPeriod $period)
    {
        if ($period->status === 'paid') {
            return back()->with('error', 'Kỳ đã chi lương không thể xoá.');
        }

        $period->delete();

        return redirect()->route('payroll.periods.index')->with('status', 'Đã xoá kỳ lương.');
    }
}
