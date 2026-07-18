<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payslip;

class MyPayslipController extends Controller
{
    public function index()
    {
        $employee = $this->currentEmployee();

        $payslips = collect();
        if ($employee) {
            $payslips = Payslip::where('employee_id', $employee->id)
                ->whereHas('period', fn ($q) => $q->whereIn('status', ['approved', 'paid']))
                ->with('period')
                ->get()
                ->sortByDesc(fn ($p) => sprintf('%04d%02d', $p->period->year, $p->period->month))
                ->values();
        }

        return view('payroll.my', compact('employee', 'payslips'));
    }

    private function currentEmployee(): ?Employee
    {
        $user = auth()->user();

        return $user?->employee ?? Employee::where('email', $user?->email)->first();
    }
}
