<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
	
	
	public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->string('laravel_site_url')->nullable()->after('wp_app_password');
            $table->string('laravel_api_token')->nullable()->after('laravel_site_url');
            $table->boolean('post_to_laravel')->default(false)->after('laravel_api_token');
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn(['laravel_site_url', 'laravel_api_token', 'post_to_laravel']);
        });
    }
	
	
	
	
};
