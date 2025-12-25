<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * রান করার সময় এই মেথডটি কার্যকর হবে।
     */
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            // ফেসবুক পোস্টে কমেন্টে লিঙ্ক যাবে কি না তার জন্য কলাম
            // এটি 'post_to_fb' কলামের ঠিক পরে যুক্ত হবে
            $table->boolean('fb_comment_link')->default(false)->after('post_to_fb');
        });
    }

    /**
     * রোলব্যাক করার সময় এই মেথডটি কার্যকর হবে।
     */
    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            // রোলব্যাক করলে কলামটি রিমুভ হয়ে যাবে
            $table->dropColumn('fb_comment_link');
        });
    }
};