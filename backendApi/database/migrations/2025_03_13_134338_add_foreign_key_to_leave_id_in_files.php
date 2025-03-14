<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    // 為files表的leave_id設定外鍵
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->foreign('leave_id')
                ->references('id')
                ->on('leaves')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropForeign(['leave_id']);
        });
    }
};
