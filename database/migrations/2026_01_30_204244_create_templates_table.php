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
    Schema::create('templates', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // যেমন: NTV Style, RTV Style
        $table->string('thumbnail_url')->nullable(); // প্রিভিউ ইমেজ
        $table->string('frame_url'); // মূল ব্ল্যাঙ্ক ফ্রেম (PNG)
        $table->json('layout_data'); // 🔥 যাদু এখানে: টাইটেল/ইমেজের পজিশন JSON এ থাকবে
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
