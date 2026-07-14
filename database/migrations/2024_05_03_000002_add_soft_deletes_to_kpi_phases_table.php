<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_phases', function (Blueprint $table) {
            if (! Schema::hasColumn('kpi_phases', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_phases', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_phases', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
