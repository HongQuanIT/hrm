<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('personal_email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('dob')->nullable();
            $table->string('national_id')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('nationality')->default('Việt Nam');
            $table->string('permanent_address')->nullable();
            $table->string('temporary_address')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('position')->nullable();
            $table->string('level')->nullable();
            $table->string('contract_type')->nullable();
            $table->date('join_date')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('status', ['active', 'on_leave', 'resigned'])->default('active');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_holder')->nullable();
            $table->decimal('base_salary', 15, 2)->nullable();
            $table->decimal('lunch_allowance', 15, 2)->nullable();
            $table->string('emergency_contact')->nullable();
            $table->json('skills')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
