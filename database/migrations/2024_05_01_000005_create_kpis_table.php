<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('measure_type', ['percent', 'count', 'milestone'])->default('percent');
            $table->string('unit')->nullable();
            $table->decimal('target_value', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->default(0);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['on_track', 'in_progress', 'behind', 'done'])->default('in_progress');
            $table->date('deadline')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained('kpis')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('assignee_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_phases');
        Schema::dropIfExists('kpis');
    }
};
