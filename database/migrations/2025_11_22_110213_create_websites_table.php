<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_websites_table.php

public function up(): void
{
    Schema::create('websites', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // যেমন: Dhaka Post
        $table->string('url');  // যেমন: https://www.dhakapost.com/
        // CSS Selectors (কোনটা টাইটেল, কোনটা ছবি চিনিয়ে দেয়ার জন্য)
        $table->string('selector_container'); // প্রতিটি নিউজের মেইন ডিভ (e.g. .news-item)
        $table->string('selector_title');     // (e.g. h2.title)
        $table->string('selector_image')->nullable();
        $table->string('selector_content')->nullable(); // ফুল নিউজের জন্য
        $table->string('selector_time')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
