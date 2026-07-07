<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Đồng bộ enum leave_requests.type để bao gồm loại 'monthly' (nghỉ phép tháng)
 * và đặt làm mặc định. Idempotent trên MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `leave_requests` MODIFY `type` ENUM('monthly','annual','sick','unpaid','maternity','remote') NOT NULL DEFAULT 'monthly'");
    }

    public function down(): void
    {
        DB::statement("UPDATE `leave_requests` SET `type` = 'annual' WHERE `type` = 'monthly'");
        DB::statement("ALTER TABLE `leave_requests` MODIFY `type` ENUM('annual','sick','unpaid','maternity','remote') NOT NULL DEFAULT 'annual'");
    }
};
