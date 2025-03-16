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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade'); // 申請人
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade'); // 假別
            
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable(); // 請假開始時間
            $table->time('end_time')->nullable(); // 請假結束時間
            $table->integer('hours'); // 總請假時數 (小時)
            $table->string('attachment')->nullable(); // 附件
            $table->text('reason')->nullable();
            
            // 主管審核
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null'); // 主管
            $table->enum('manager_status', ['pending', 'approved', 'rejected'])->default('pending'); // 主管審核
            $table->text('manager_remarks')->nullable(); // 主管審核意見
        
            // HR 最終審核
            $table->foreignId('hr_id')->nullable()->constrained('users')->onDelete('set null'); // HR
            $table->enum('hr_status', ['pending', 'approved', 'rejected'])->default('pending'); // HR 審核
            $table->text('hr_remarks')->nullable(); // HR 審核意見
        
            // `final_status` 來統一管理請假狀態
            $table->enum('final_status', ['pending', 'manager_approved', 'approved', 'rejected', 'canceled'])->default('pending');
        
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
