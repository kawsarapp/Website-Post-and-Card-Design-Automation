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
            $table->text('openai_api_key')->nullable()->after('proxy_password');
            $table->string('openai_model')->nullable()->after('openai_api_key');
            
            $table->text('gemini_api_key')->nullable()->after('openai_model');
            $table->string('gemini_model')->nullable()->after('gemini_api_key');
            
            $table->text('deepseek_api_key')->nullable()->after('gemini_model');
            $table->string('deepseek_model')->nullable()->after('deepseek_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn([
                'openai_api_key', 'openai_model',
                'gemini_api_key', 'gemini_model',
                'deepseek_api_key', 'deepseek_model'
            ]);
        });
    }
};
