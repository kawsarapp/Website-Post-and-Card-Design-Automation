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
    Schema::create('telegram_subscribers', function (Blueprint $table) {
        $table->id();
        $table->string('chat_id')->unique(); // ইউনিক আইডি সেভ হবে
        $table->string('first_name')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_subscribers');
    }
};
