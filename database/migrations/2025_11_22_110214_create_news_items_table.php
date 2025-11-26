<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_create_news_items_table.php

public function up(): void
{
    Schema::create('news_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('website_id')->constrained()->onDelete('cascade');
        $table->text('title'); // টাইটেল বড় হতে পারে তাই text
        $table->string('thumbnail_url')->nullable();
        $table->longText('content')->nullable(); // ফুল নিউজ স্টোরেজ
        $table->string('original_link')->unique(); // ডুপ্লিকেট নিউজ আটকানোর জন্য
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};
