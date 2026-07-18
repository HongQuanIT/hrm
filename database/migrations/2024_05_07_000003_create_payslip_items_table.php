<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $table->enum('type', ['earning', 'deduction']);
            $table->string('code')->nullable();
            $table->string('label');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_system')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['payslip_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
    }
};
