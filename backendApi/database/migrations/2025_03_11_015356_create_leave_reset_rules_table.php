<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // 假別規則資料表
    public function up(): void
    {
        Schema::create('leave_reset_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->string('rule_type');  // yearly, monthly
            $table->string('rule_value')->nullable();  // 01-01 / 1 / 90
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_reset_rules');
    }
};