<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->integer('total_minutes')->default(0);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->enum('status', ['on_time', 'late', 'absent', 'leave', 'working'])->default('on_time');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
