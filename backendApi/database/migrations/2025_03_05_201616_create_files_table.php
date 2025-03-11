<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // 使用者 ID
            $table->unsignedBigInteger('leave_id')->nullable(); // 請假 ID (可為 NULL)
            $table->string('avatar')->nullable(); // 大頭貼
            $table->string('leave_attachment')->nullable(); // 請假附件
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
