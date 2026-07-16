<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_phases', function (Blueprint $table) {
            if (! Schema::hasColumn('kpi_phases', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('kpi_phases', 'priority')) {
                $table->string('priority')->default('medium')->after('description');
            }
            if (! Schema::hasColumn('kpi_phases', 'start_date')) {
                $table->date('start_date')->nullable()->after('assignee_employee_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_phases', function (Blueprint $table) {
            foreach (['description', 'priority', 'start_date'] as $column) {
                if (Schema::hasColumn('kpi_phases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
