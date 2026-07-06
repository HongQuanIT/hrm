<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['monthly', 'annual', 'sick', 'unpaid', 'maternity', 'remote'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 4, 1)->default(1);
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->string('approver_name')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
