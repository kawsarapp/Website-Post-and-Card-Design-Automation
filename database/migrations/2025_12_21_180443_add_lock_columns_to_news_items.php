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
			$table->foreignId('locked_by_user_id')->nullable()->constrained('users')->onDelete('set null');
			$table->timestamp('locked_at')->nullable();
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
