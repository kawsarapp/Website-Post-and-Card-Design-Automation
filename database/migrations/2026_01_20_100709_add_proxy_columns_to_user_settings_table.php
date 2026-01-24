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
    Schema::table('user_settings', function (Blueprint $table) {
        $table->string('proxy_host')->nullable()->after('laravel_route_prefix');
        $table->string('proxy_port')->nullable()->after('proxy_host');
        $table->string('proxy_username')->nullable()->after('proxy_port');
        $table->string('proxy_password')->nullable()->after('proxy_username');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            //
        });
    }
};
