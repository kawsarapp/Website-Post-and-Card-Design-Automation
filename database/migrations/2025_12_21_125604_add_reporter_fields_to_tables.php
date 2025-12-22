<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
	public function up()
		{
			// ১. ইউজার টেবিলে parent_id যোগ করা (রিপোর্টার কার লোক তা চেনার জন্য)
			Schema::table('users', function (Blueprint $table) {
				$table->unsignedBigInteger('parent_id')->nullable()->after('id');
				$table->string('reporter_location')->nullable()->after('role'); // রিপোর্টারের এলাকা
			});

			// ২. নিউজ আইটেম টেবিলে নতুন ক্ষেত্রগুলো যোগ করা
			Schema::table('news_items', function (Blueprint $table) {
				$table->unsignedBigInteger('reporter_id')->nullable()->after('user_id');
				$table->string('location')->nullable();       // এলাকা
				$table->text('short_summary')->nullable();     // শর্ট সামারি
				$table->string('image_caption')->nullable();   // ছবির ক্যাপশন
				$table->string('tags')->nullable();            // ট্যাগস
				$table->string('reporter_name_manual')->nullable(); // রিপোর্টারের নাম (যদি ম্যানুয়ালি দিতে চায়)
			});
		}

		public function down()
		{
			Schema::table('users', function (Blueprint $table) {
				$table->dropColumn(['parent_id', 'reporter_location']);
			});
			Schema::table('news_items', function (Blueprint $table) {
				$table->dropColumn(['reporter_id', 'location', 'short_summary', 'image_caption', 'tags', 'reporter_name_manual']);
			});
		}

};
