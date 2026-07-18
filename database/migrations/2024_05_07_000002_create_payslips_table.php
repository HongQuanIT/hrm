<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('lunch_allowance', 15, 2)->default(0);
            $table->unsignedTinyInteger('days_in_month');
            $table->decimal('present_days', 5, 1)->default(0);
            $table->decimal('paid_leave_days', 5, 1)->default(0);
            $table->decimal('unpaid_leave_days', 5, 1)->default(0);
            $table->decimal('absent_days', 5, 1)->default(0);
            $table->decimal('unpaid_days', 5, 1)->default(0);
            $table->decimal('paid_days', 5, 1)->default(0);
            $table->integer('late_count')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('deduction_total', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->string('bank_snapshot')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
