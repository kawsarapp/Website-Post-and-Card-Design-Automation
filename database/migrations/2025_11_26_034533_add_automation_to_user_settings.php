<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // চেক করবে কলাম আছে কিনা। না থাকলে বানাবে।
        Schema::table('user_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('user_settings', 'is_auto_posting')) {
                $table->boolean('is_auto_posting')->default(false);
            }
            if (!Schema::hasColumn('user_settings', 'auto_post_interval')) {
                $table->integer('auto_post_interval')->default(10);
            }
        });
    }

    public function down()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            if (Schema::hasColumn('user_settings', 'is_auto_posting')) {
                $table->dropColumn(['is_auto_posting', 'auto_post_interval']);
            }
        });
    }
};