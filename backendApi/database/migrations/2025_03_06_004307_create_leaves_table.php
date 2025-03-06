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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // 員工ID

            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->dateTime('start_time');    // 開始時間
            $table->dateTime('end_time');      // 結束時間
            $table->integer('leave_hours');      // 請假時數

            $table->text('reason');                            // 事由
            $table->string('status', 20)->default('pending');  // 狀態
            $table->text('reject_reason');                     // 退回原因
            $table->foreignId('attachment_id')->nullable()->constrained('files')->onDelete('set null');
            $table->timestamps();  // created_at 和 updated_at
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
