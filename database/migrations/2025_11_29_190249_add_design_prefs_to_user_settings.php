<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
			public function up()
		{
			Schema::table('user_settings', function (Blueprint $table) {
				$table->json('design_preferences')->nullable(); // ডিজাইন সেভ রাখার জন্য
			});
		}

		public function down()
		{
			Schema::table('user_settings', function (Blueprint $table) {
				$table->dropColumn('design_preferences');
			});
		}
	
};
