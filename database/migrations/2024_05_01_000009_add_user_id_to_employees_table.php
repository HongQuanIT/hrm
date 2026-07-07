<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bổ sung cột liên kết employees.user_id cho các môi trường đã chạy bản
 * create migration cũ (trước khi cột này được thêm). An toàn khi chạy lại.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')
                    ->constrained('users')->nullOnDelete();
            });
        }

        // Liên kết nhân viên hiện có với tài khoản trùng email (idempotent).
        DB::statement('UPDATE employees e JOIN users u ON u.email = e.email SET e.user_id = u.id WHERE e.user_id IS NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'user_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
