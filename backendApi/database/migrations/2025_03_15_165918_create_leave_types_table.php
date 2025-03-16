<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 假別名稱（如：特休假）
            $table->string('code')->unique(); // 假別代碼（如：annual, sick）
            $table->integer('default_hours')->nullable(); // 預設時數 (有些假沒有固定時數)
            $table->enum('gender_limit', ['male', 'female'])->nullable(); // 限定性別
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
