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
			Schema::table('news_items', function (Blueprint $table) {
				// ১. আগের ইউনিক ইনডেক্স ড্রপ করা
				$table->dropUnique(['original_link']); 

				// ২. নতুন কম্পোজিট ইউনিক ইনডেক্স দেওয়া (User + Link)
				$table->unique(['user_id', 'original_link']);
			});
		}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
