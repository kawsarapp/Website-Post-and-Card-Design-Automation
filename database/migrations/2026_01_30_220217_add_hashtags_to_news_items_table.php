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
        // যদি কলাম না থাকে তবেই যোগ করবে
        if (!Schema::hasColumn('news_items', 'hashtags')) {
            $table->text('hashtags')->nullable()->after('content');
        }
    });
}

public function down()
{
    Schema::table('news_items', function (Blueprint $table) {
        $table->dropColumn('hashtags');
    });
}
   
   
};
