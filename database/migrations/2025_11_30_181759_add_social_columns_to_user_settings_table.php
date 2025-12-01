<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
	
		public function up()
			{
				Schema::table('user_settings', function (Blueprint $table) {
					// Facebook Columns
					$table->string('fb_page_id')->nullable()->after('wp_app_password');
					$table->text('fb_access_token')->nullable()->after('fb_page_id');

					// Telegram Columns (যদি আগে না থাকে)
					if (!Schema::hasColumn('user_settings', 'telegram_bot_token')) {
						$table->string('telegram_bot_token')->nullable()->after('fb_access_token');
					}
					
					// WhatsApp Columns (ভবিষ্যতের জন্য)
					$table->string('whatsapp_number_id')->nullable()->after('telegram_bot_token');
					$table->text('whatsapp_access_token')->nullable()->after('whatsapp_number_id');
				});
			}

		public function down()
			{
				Schema::table('user_settings', function (Blueprint $table) {
					$table->dropColumn([
						'fb_page_id', 
						'fb_access_token', 
						'telegram_bot_token',
						'whatsapp_number_id',
						'whatsapp_access_token'
					]);
				});
			}
	
};
