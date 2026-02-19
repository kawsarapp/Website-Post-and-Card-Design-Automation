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
        $table->string('custom_api_url')->nullable()->after('laravel_route_prefix'); // নিউজ পাঠানোর কাস্টম লিংক
        $table->string('custom_category_url')->nullable()->after('custom_api_url'); // ক্যাটাগরি আনার কাস্টম লিংক
        $table->longText('custom_api_mapping')->nullable()->after('custom_category_url'); // ফিল্ডগুলো মেলানোর JSON
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
