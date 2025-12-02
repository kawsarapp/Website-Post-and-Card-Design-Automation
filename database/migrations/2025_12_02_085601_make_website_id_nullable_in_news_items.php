<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('news_items', function (Blueprint $table) {
            // website_id কে nullable বা অপশনাল করা হচ্ছে
            $table->unsignedBigInteger('website_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->unsignedBigInteger('website_id')->nullable(false)->change();
        });
    }
};