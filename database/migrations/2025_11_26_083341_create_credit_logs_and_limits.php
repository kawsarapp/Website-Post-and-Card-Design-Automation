<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
			public function up()
		{
			// ১. ক্রেডিট লগ টেবিল
			Schema::create('credit_histories', function (Blueprint $table) {
				$table->id();
				$table->foreignId('user_id')->constrained()->onDelete('cascade');
				$table->string('action_type'); // 'auto_post', 'manual_post', 'purchase', 'admin_bonus'
				$table->string('description')->nullable(); // নিউজ টাইটেল বা নোট
				$table->integer('credits_change'); // কত ক্রেডিট কমলো (-) বা বাড়লো (+)
				$table->integer('balance_after'); // ওই সময়ের ব্যালেন্স
				$table->timestamps();
			});

			// ২. ইউজার টেবিলে ডেইলি লিমিট কলাম
			Schema::table('users', function (Blueprint $table) {
				$table->integer('daily_post_limit')->default(20); // ডিফল্ট ২০টি পোস্ট পার ডে
			});
		}

		public function down()
		{
			Schema::dropIfExists('credit_histories');
			Schema::table('users', function (Blueprint $table) {
				$table->dropColumn('daily_post_limit');
			});
		}
	
	
	
};
