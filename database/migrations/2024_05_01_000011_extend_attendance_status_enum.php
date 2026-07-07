<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Đồng bộ enum attendances.status về tập giá trị hiện hành
 * (bao gồm 'working' và 'missing_checkout'). Idempotent trên MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `attendances` MODIFY `status` ENUM('on_time','late','absent','leave','working','missing_checkout') NOT NULL DEFAULT 'on_time'");
    }

    public function down(): void
    {
        // Đưa các trạng thái mới về 'absent' trước khi thu hẹp enum để tránh lỗi dữ liệu.
        DB::statement("UPDATE `attendances` SET `status` = 'absent' WHERE `status` = 'missing_checkout'");
        DB::statement("ALTER TABLE `attendances` MODIFY `status` ENUM('on_time','late','absent','leave','working') NOT NULL DEFAULT 'on_time'");
    }
};
