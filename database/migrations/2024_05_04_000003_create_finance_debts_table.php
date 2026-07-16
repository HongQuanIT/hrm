<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_debts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['receivable', 'payable']);
            $table->string('partner_name');
            $table->string('partner_contact')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('due_date')->nullable();
            $table->enum('status', ['open', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('open');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_debts');
    }
};
