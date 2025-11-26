<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('news_items', function (Blueprint $table) {
            // ১. Rewritten Content কলাম (AI লেখা সেভ করার জন্য)
            if (!Schema::hasColumn('news_items', 'rewritten_content')) {
                $table->longText('rewritten_content')->nullable();
            }

            // ২. স্ট্যাটাস কলাম (পোস্ট হয়েছে কিনা)
            if (!Schema::hasColumn('news_items', 'is_posted')) {
                $table->boolean('is_posted')->default(false);
            }

            // ৩. ওয়ার্ডপ্রেস পোস্ট আইডি (ফিউচার রেফারেন্সের জন্য)
            if (!Schema::hasColumn('news_items', 'wp_post_id')) {
                $table->unsignedBigInteger('wp_post_id')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('news_items', function (Blueprint $table) {
            $columns = ['rewritten_content', 'is_posted', 'wp_post_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('news_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};