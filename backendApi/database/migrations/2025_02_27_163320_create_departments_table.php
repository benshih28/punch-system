<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();   // 部門 ID，自動遞增
            $table->string('name')->unique(); // 部門名稱，不可重複
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('cascade'); // 主管 ID，參考 users.user_id
            $table->timestamps();  // 建立時間 & 更新時間
        });
    }

    public function down()
    {
        Schema::dropIfExists('departments');
    }
};