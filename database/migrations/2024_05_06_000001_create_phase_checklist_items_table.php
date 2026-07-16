<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_phase_id')->constrained('kpi_phases')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_done')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_checklist_items');
    }
};
