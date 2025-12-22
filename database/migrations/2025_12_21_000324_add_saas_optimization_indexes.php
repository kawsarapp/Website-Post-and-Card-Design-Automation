<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('news_items', function (Blueprint $table) {
            // ১. অটোমেশন কুয়েরি ফাস্ট করার জন্য (সবচেয়ে গুরুত্বপূর্ণ)
            // console.php তে: where('user_id', ..)->where('is_posted', ..)->where('is_queued', ..)
            $table->index(['user_id', 'is_posted', 'is_queued'], 'idx_autopost_fast');
            
            // ২. ড্যাশবোর্ড সার্চ ও ফিল্টারের জন্য
            $table->index(['website_id', 'status']);
            $table->index('created_at'); // oldest() / latest() এর জন্য
            
            // ৩. স্ট্যাটাস ট্র্যাকিং
            $table->index('fb_status');
        });
    }

    public function down()
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropIndex('idx_autopost_fast');
            $table->dropIndex(['website_id', 'status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['fb_status']);
        });
    }
};