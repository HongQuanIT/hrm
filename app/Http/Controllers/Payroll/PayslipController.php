<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayslipItemRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Services\PayrollService;

class PayslipController extends Controller
{
    public function show(PayrollPeriod $period, Payslip $payslip)
    {
        abort_unless($payslip->payroll_period_id === $period->id, 404);

        $user = auth()->user();
        $isOwner = $this->currentEmployee()?->id === $payslip->employee_id;

        // Nhân viên chỉ xem được phiếu của mình khi kỳ đã duyệt/đã chi.
        abort_unless(
            $user->isSuperAdmin() || ($isOwner && in_array($period->status, ['approved', 'paid'], true)),
            403,
            'Bạn không có quyền xem phiếu lương này.'
        );

        $payslip->load(['employee', 'items', 'period']);

        return view('payroll.payslip', [
            'period' => $period,
            'payslip' => $payslip,
            'canEdit' => $user->isSuperAdmin() && ! $period->is_locked,
        ]);
    }

    public function storeItem(StorePayslipItemRequest $request, PayrollPeriod $period, Payslip $payslip, PayrollService $payroll)
    {
        abort_unless($payslip->payroll_period_id === $period->id, 404);

        if ($period->is_locked) {
            return back()->with('error', 'Kỳ đã khoá, không thể thêm khoản.');
        }

        $data = $request->validated();
        $payslip->items()->create([
            'type' => $data['type'],
            'label' => $data['label'],
            'amount' => $data['amount'],
            'is_system' => false,
        ]);

        $payroll->refreshTotals($payslip);

        return back()->with('status', 'Đã thêm khoản vào phiếu lương.');
    }

    public function destroyItem(PayrollPeriod $period, Payslip $payslip, PayslipItem $item, PayrollService $payroll)
    {
        abort_unless($payslip->payroll_period_id === $period->id && $item->payslip_id === $payslip->id, 404);

        if ($period->is_locked) {
            return back()->with('error', 'Kỳ đã khoá, không thể xoá khoản.');
        }

        if ($item->is_system) {
            return back()->with('error', 'Không thể xoá dòng hệ thống (Lương theo công / Phụ cấp).');
        }

        $item->delete();
        $payroll->refreshTotals($payslip);

        return back()->with('status', 'Đã xoá khoản khỏi phiếu lương.');
    }

    private function currentEmployee(): ?Employee
    {
        $user = auth()->user();

        return $user?->employee ?? Employee::where('email', $user?->email)->first();
    }
}
