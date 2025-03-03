<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id(); // 職位 ID，自動遞增
            $table->string('name'); // 職位名稱
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade'); // 部門 ID，參考 departments.department_id
            $table->timestamps();  // 建立時間 & 更新時間
        });
    }

    public function down()
    {
        Schema::dropIfExists('positions');
    }
};