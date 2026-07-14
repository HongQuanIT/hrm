<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_requests', 'half_day')) {
                // null = nghỉ nguyên ngày; 'morning'/'afternoon' = nghỉ nửa ngày (0.5 công).
                $table->string('half_day')->nullable()->after('days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'half_day')) {
                $table->dropColumn('half_day');
            }
        });
    }
};
