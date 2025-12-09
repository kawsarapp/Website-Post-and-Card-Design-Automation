<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('news_items', function (Blueprint $table) {
        // posted_at কলাম যোগ করা হচ্ছে
        $table->timestamp('posted_at')->nullable()->after('is_posted');
    });
}

public function down()
{
    Schema::table('news_items', function (Blueprint $table) {
        $table->dropColumn('posted_at');
    });
}
	
};
