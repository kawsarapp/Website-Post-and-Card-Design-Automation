<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
	public function up()
		{
			Schema::table('news_items', function (Blueprint $table) {
				// Facebook Status
				$table->string('fb_status')->default('pending')->nullable(); // pending, success, failed, skipped
				$table->text('fb_error')->nullable(); // Error message if failed

				// Telegram Status
				$table->string('tg_status')->default('pending')->nullable(); // pending, success, failed, skipped
				$table->text('tg_error')->nullable(); // Error message if failed
			});
		}

		public function down()
		{
			Schema::table('news_items', function (Blueprint $table) {
				$table->dropColumn(['fb_status', 'fb_error', 'tg_status', 'tg_error']);
			});
		}
	
};
