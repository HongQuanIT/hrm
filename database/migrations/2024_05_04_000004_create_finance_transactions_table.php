<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->foreignId('debt_id')->nullable()->constrained('finance_debts')->nullOnDelete();
            $table->enum('direction', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->boolean('is_contribution')->default(false);
            $table->string('contributor_name')->nullable();
            $table->date('occurred_on');
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'occurred_on']);
            $table->index(['direction', 'is_contribution']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
