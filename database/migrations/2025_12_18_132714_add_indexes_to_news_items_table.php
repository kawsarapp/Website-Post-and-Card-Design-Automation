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
				// এই কলামগুলো দিয়ে আমরা প্রায়ই সার্চ/ফিল্টার করি, তাই ইনডেক্স করা জরুরি
				$table->index('user_id');
				$table->index('website_id');
				$table->index('is_posted');
				$table->index('posted_at');
			});
		}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            //
        });
    }
};
