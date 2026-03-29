<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('user_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('user_settings', 'auto_clean_days')) {
                    $table->integer('auto_clean_days')->default(7)->after('is_auto_posting');
                }
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('news_items', function (Blueprint $table) {
                $table->index('created_at');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('news_items', function (Blueprint $table) {
                $table->index(['user_id', 'is_posted', 'is_queued'], 'news_queue_optimization_index');
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_settings_and_indexes', function (Blueprint $table) {
            //
        });
    }
};
