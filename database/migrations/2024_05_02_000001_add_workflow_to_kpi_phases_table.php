<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bổ sung trạng thái "received" (Đã nhận việc) vào workflow của giai đoạn.
        DB::statement("ALTER TABLE kpi_phases MODIFY status ENUM('pending', 'received', 'in_progress', 'done') NOT NULL DEFAULT 'pending'");

        Schema::table('kpi_phases', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('status');
            $table->timestamp('started_at')->nullable()->after('received_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_phases', function (Blueprint $table) {
            $table->dropColumn(['received_at', 'started_at', 'completed_at']);
        });

        DB::statement("UPDATE kpi_phases SET status = 'pending' WHERE status = 'received'");
        DB::statement("ALTER TABLE kpi_phases MODIFY status ENUM('pending', 'in_progress', 'done') NOT NULL DEFAULT 'pending'");
    }
};
