<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thêm sau khi bảng employees tồn tại để tạo được khóa ngoại.
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('head_employee_id')->nullable()->after('code')
                ->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_employee_id');
        });
    }
};
