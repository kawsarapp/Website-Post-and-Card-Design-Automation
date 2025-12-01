<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
	public function up()
	{
		Schema::table('user_settings', function (Blueprint $table) {
			$table->boolean('post_to_fb')->default(true);       // ডিফল্ট অন থাকবে
			$table->boolean('post_to_telegram')->default(true); // ডিফল্ট অন থাকবে
			$table->boolean('post_to_whatsapp')->default(true); // ডিফল্ট অন থাকবে
		});
	}

	public function down()
	{
		Schema::table('user_settings', function (Blueprint $table) {
			$table->dropColumn(['post_to_fb', 'post_to_telegram', 'post_to_whatsapp']);
		});
	}
		
	
	
};
