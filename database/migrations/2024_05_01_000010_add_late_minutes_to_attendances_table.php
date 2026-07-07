<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bổ sung cột attendances.late_minutes cho môi trường đã chạy bản create
 * migration cũ. An toàn khi cột đã tồn tại.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('attendances', 'late_minutes')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedSmallInteger('late_minutes')->default(0)->after('total_minutes');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('attendances', 'late_minutes')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('late_minutes');
        });
    }
};
