<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('news_items', function (Blueprint $table) {
            if (!Schema::hasColumn('news_items', 'staff_id')) {
                $table->unsignedBigInteger('staff_id')->nullable()->after('user_id');
            }
        });

        Schema::table('credit_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('credit_histories', 'staff_id')) {
                $table->unsignedBigInteger('staff_id')->nullable()->after('user_id');
            }
        });
    }

    public function down()
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropColumn('staff_id');
        });
        Schema::table('credit_histories', function (Blueprint $table) {
            $table->dropColumn('staff_id');
        });
    }
};