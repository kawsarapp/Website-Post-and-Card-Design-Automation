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
        Schema::table('user_settings', function (Blueprint $table) {
        $table->json('allowed_templates')->nullable(); // অনুমতি পাওয়া টেমপ্লেট লিস্ট
        $table->string('default_template')->default('dhaka_post_card'); // ডিফল্ট টেমপ্লেট
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
        $table->dropColumn(['allowed_templates', 'default_template']);
    });
    }
};
