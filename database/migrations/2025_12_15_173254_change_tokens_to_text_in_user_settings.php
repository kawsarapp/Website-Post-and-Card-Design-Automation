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
        // এনক্রিপটেড ডাটা রাখার জন্য string থেকে text এ পরিবর্তন করা হচ্ছে
        $table->text('wp_app_password')->nullable()->change();
        $table->text('fb_access_token')->nullable()->change();     // ফেসবুক টোকেন অনেক বড় হয়
        $table->text('telegram_bot_token')->nullable()->change();
        $table->text('laravel_api_token')->nullable()->change();
    });
}

public function down()
{
    Schema::table('user_settings', function (Blueprint $table) {
        // রিভার্স করা (প্রয়োজন হলে)
        $table->string('wp_app_password')->nullable()->change();
        $table->string('fb_access_token')->nullable()->change();
        $table->string('telegram_bot_token')->nullable()->change();
        $table->string('laravel_api_token')->nullable()->change();
    });
}
	
	
};
