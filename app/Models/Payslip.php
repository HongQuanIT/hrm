<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_period_id', 'employee_id', 'base_salary', 'lunch_allowance',
        'days_in_month', 'present_days', 'paid_leave_days', 'unpaid_leave_days',
        'absent_days', 'unpaid_days', 'paid_days', 'late_count', 'late_minutes',
        'overtime_minutes', 'gross_amount', 'deduction_total', 'net_amount',
        'bank_snapshot', 'note',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'lunch_allowance' => 'decimal:2',
            'days_in_month' => 'integer',
            'present_days' => 'decimal:1',
            'paid_leave_days' => 'decimal:1',
            'unpaid_leave_days' => 'decimal:1',
            'absent_days' => 'decimal:1',
            'unpaid_days' => 'decimal:1',
            'paid_days' => 'decimal:1',
            'gross_amount' => 'decimal:2',
            'deduction_total' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(PayslipItem::class)->where('type', 'earning');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayslipItem::class)->where('type', 'deduction');
    }
}
