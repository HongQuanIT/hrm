<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /** Loại nghỉ được tính là phép CÓ lương (tính vào quota). */
    private const PAID_LEAVE_TYPES = ['monthly', 'annual', 'sick', 'maternity'];

    public function daysInMonth(int $year, int $month): int
    {
        return (int) Carbon::create($year, $month, 1)->daysInMonth;
    }

    /** Quota ngày phép có lương của kỳ, co theo tỷ lệ ngày đi làm (BR-M11-06). */
    public function paidLeaveQuota(float $presentDays, int $daysInMonth): float
    {
        if ($daysInMonth <= 0) {
            return 0;
        }

        $monthlyQuota = (int) CompanySetting::get('leave_days_per_month', 6);

        return round($monthlyQuota * $presentDays / $daysInMonth);
    }

    /**
     * Tính (hoặc tính lại) toàn bộ phiếu lương của một kỳ.
     * Giữ nguyên các khoản THỦ CÔNG admin đã thêm; chỉ sinh lại dòng hệ thống (base, lunch).
     */
    public function calculatePeriod(PayrollPeriod $period): int
    {
        $employees = Employee::whereIn('status', ['active', 'on_leave'])->orderBy('name')->get();

        DB::transaction(function () use ($period, $employees) {
            foreach ($employees as $employee) {
                $this->buildPayslip($period, $employee);
            }
        });

        return $employees->count();
    }

    public function buildPayslip(PayrollPeriod $period, Employee $employee): Payslip
    {
        $year = $period->year;
        $month = $period->month;
        $daysInMonth = $period->days_in_month;

        $baseSalary = (float) ($employee->base_salary ?? 0);
        $lunchAllowance = (float) ($employee->lunch_allowance ?? 0);
        // Lương THÁNG = lương cơ bản + phụ cấp; lương NGÀY = lương tháng / số ngày trong tháng.
        $monthlySalary = $baseSalary + $lunchAllowance;
        $daily = $daysInMonth > 0 ? $monthlySalary / $daysInMonth : 0;

        // Chấm công trong tháng
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $month)
            ->get();

        $presentDays = $attendances->whereIn('status', ['on_time', 'late', 'working', 'missing_checkout'])->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $lateMinutes = (int) $attendances->sum('late_minutes');
        $missingCheckout = $attendances->where('status', 'missing_checkout')->count();

        // Nghỉ phép approved trong tháng (theo start_date, thống nhất với LeaveController)
        $leaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->get();

        $paidTypeDays = (float) $leaves->whereIn('type', self::PAID_LEAVE_TYPES)->sum('days');
        $unpaidTypeDays = (float) $leaves->where('type', 'unpaid')->sum('days');

        // Quota phép co theo tỷ lệ đi làm → phần vượt quota tính không lương
        $quota = $this->paidLeaveQuota($presentDays, $daysInMonth);
        $paidLeaveDays = min($paidTypeDays, $quota);
        $excessLeaveDays = max($paidTypeDays - $paidLeaveDays, 0);
        $unpaidLeaveDays = $unpaidTypeDays + $excessLeaveDays;

        $unpaidDays = $unpaidLeaveDays + $absentDays;
        $paidDays = max($daysInMonth - $unpaidDays, 0);

        // Tổng lương theo công (gồm cả phụ cấp) = lương ngày × số ngày được trả.
        $workPay = (int) round($daily * $paidDays);
        // Tách phần phụ cấp để hiển thị; phần lương cơ bản = tổng − phụ cấp (đảm bảo cộng khớp tuyệt đối).
        $lunchPay = ($daysInMonth > 0 && $lunchAllowance > 0) ? (int) round(($lunchAllowance / $daysInMonth) * $paidDays) : 0;
        $basePay = $workPay - $lunchPay;

        $payslip = Payslip::updateOrCreate(
            ['payroll_period_id' => $period->id, 'employee_id' => $employee->id],
            [
                'base_salary' => $baseSalary,
                'lunch_allowance' => $lunchAllowance,
                'days_in_month' => $daysInMonth,
                'present_days' => $presentDays,
                'paid_leave_days' => $paidLeaveDays,
                'unpaid_leave_days' => $unpaidLeaveDays,
                'absent_days' => $absentDays,
                'unpaid_days' => $unpaidDays,
                'paid_days' => $paidDays,
                'late_count' => $lateCount,
                'late_minutes' => $lateMinutes,
                'bank_snapshot' => $this->bankSnapshot($employee),
                'note' => $missingCheckout > 0
                    ? sprintf('Có %d ngày quên check-out — cần rà soát.', $missingCheckout)
                    : null,
            ]
        );

        // Sinh lại các dòng hệ thống, giữ nguyên khoản thủ công.
        $payslip->items()->where('is_system', true)->delete();

        $payslip->items()->create([
            'type' => 'earning',
            'code' => 'base',
            'label' => 'Lương cơ bản theo công',
            'amount' => $basePay,
            'is_system' => true,
            'meta' => [
                'monthly_salary' => $monthlySalary,
                'daily_rate' => round($daily),
                'paid_days' => $paidDays,
                'unpaid_days' => $unpaidDays,
            ],
        ]);

        if ($lunchPay > 0) {
            $payslip->items()->create([
                'type' => 'earning',
                'code' => 'lunch',
                'label' => 'Phụ cấp (ăn trưa, đi lại, chỗ ở...)',
                'amount' => $lunchPay,
                'is_system' => true,
                'meta' => ['paid_days' => $paidDays],
            ]);
        }

        $this->refreshTotals($payslip);

        return $payslip;
    }

    /** Cộng lại gross/deduction/net từ các dòng của phiếu. */
    public function refreshTotals(Payslip $payslip): void
    {
        $payslip->loadMissing('items');
        $gross = (float) $payslip->items->where('type', 'earning')->sum('amount');
        $deduction = (float) $payslip->items->where('type', 'deduction')->sum('amount');

        $payslip->update([
            'gross_amount' => $gross,
            'deduction_total' => $deduction,
            'net_amount' => max($gross - $deduction, 0),
        ]);
    }

    /**
     * Chi lương cả kỳ: sinh MỘT giao dịch chi (expense) danh mục "Lương",
     * lưu tham chiếu vào kỳ và đánh dấu đã chi (BR-M11-18/19).
     */
    public function payViaFinance(PayrollPeriod $period, int $accountId, string $occurredOn): FinanceTransaction
    {
        $category = FinanceCategory::firstOrCreate(
            ['name' => 'Lương', 'direction' => 'expense'],
            ['color' => '#ef4444']
        );

        $total = (float) $period->payslips()->sum('net_amount');

        $transaction = FinanceTransaction::create([
            'account_id' => $accountId,
            'category_id' => $category->id,
            'direction' => 'expense',
            'amount' => $total,
            'is_contribution' => false,
            'occurred_on' => $occurredOn,
            'description' => sprintf('Lương tháng %02d/%d', $period->month, $period->year),
            'created_by' => auth()->id(),
        ]);

        $period->update([
            'status' => 'paid',
            'finance_transaction_id' => $transaction->id,
        ]);

        return $transaction;
    }

    private function bankSnapshot(Employee $employee): ?string
    {
        if (! $employee->bank_account) {
            return null;
        }

        return trim(sprintf('%s - %s - %s', $employee->bank_name, $employee->bank_account, $employee->bank_holder), ' -');
    }
}
