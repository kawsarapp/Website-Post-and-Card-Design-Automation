<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('websites', function (Blueprint $table) {
        $table->string('scraper_method')->nullable(); // 'node', 'python' or null
    });

    Schema::table('user_settings', function (Blueprint $table) {
        $table->string('scraper_method')->nullable(); // 'node', 'python' or null
    });
}

public function down()
{
    Schema::table('websites', function (Blueprint $table) {
        $table->dropColumn('scraper_method');
    });
    Schema::table('user_settings', function (Blueprint $table) {
        $table->dropColumn('scraper_method');
    });
}
};
