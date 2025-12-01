<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
	public function up()
	{
		Schema::table('news_items', function (Blueprint $table) {
			// স্ট্যাটাস: new, processing, draft, published
			$table->string('status')->default('new')->after('is_posted'); 
			$table->text('ai_title')->nullable()->after('title');
			$table->longText('ai_content')->nullable()->after('content');
		});
	}

	public function down()
	{
		Schema::table('news_items', function (Blueprint $table) {
			$table->dropColumn(['status', 'ai_title', 'ai_content']);
		});
	}
	
};
