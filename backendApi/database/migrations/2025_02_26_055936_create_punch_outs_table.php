<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('punch_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('timestamp');
            $table->boolean('is_valid')->default(true); // ✅ 加入是否有效的欄位
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('punch_outs');
    }
};

