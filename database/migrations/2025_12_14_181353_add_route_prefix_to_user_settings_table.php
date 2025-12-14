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
				$table->string('laravel_route_prefix')->default('news')->nullable(); // যেমন: news, post, article
			});
		}

		public function down()
		{
			Schema::table('user_settings', function (Blueprint $table) {
				$table->dropColumn('laravel_route_prefix');
			});
		}
	
};
