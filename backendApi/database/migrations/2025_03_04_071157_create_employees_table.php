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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 員工帳號
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null'); // 部門
            $table->foreignId('position_id')->nullable()->constrained('positions')->onDelete('set null'); // 職位
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null'); // 主管
            $table->enum('status', ['pending', 'approved', 'rejected','inactive'])->default('pending'); // 員工審核狀態
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
