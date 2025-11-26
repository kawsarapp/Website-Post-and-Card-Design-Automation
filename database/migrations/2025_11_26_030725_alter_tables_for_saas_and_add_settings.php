<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ১. ইউজার টেবিলে কলামগুলো আছে কিনা চেক করে যোগ করা (যাতে ডুপ্লিকেট এরর না দেয়)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user');
            }
            if (!Schema::hasColumn('users', 'credits')) {
                $table->integer('credits')->default(10);
            }
            if (!Schema::hasColumn('users', 'total_credits_limit')) {
                $table->integer('total_credits_limit')->default(10);
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        // ২. ওয়েবসাইট টেবিলে ইউজার আইডি (Nullable)
        Schema::table('websites', function (Blueprint $table) {
            if (!Schema::hasColumn('websites', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            }
        });

        // ৩. নিউজ আইটেম টেবিলে ইউজার আইডি (Nullable)
        Schema::table('news_items', function (Blueprint $table) {
            if (!Schema::hasColumn('news_items', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            }
        });

        // ৪. ইউজার সেটিংস টেবিল (যদি না থাকে তবেই বানাবে)
        if (!Schema::hasTable('user_settings')) {
            Schema::create('user_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                
                // Branding
                $table->string('brand_name')->nullable();
                $table->string('logo_url')->nullable();
                $table->string('default_theme_color')->default('red');
                
                // WP Credentials
                $table->string('wp_url')->nullable();
                $table->string('wp_username')->nullable();
                $table->string('wp_app_password')->nullable();
                
                // Telegram
                $table->string('telegram_channel_id')->nullable();
                
                // Automation
                $table->boolean('is_auto_posting')->default(false);
                $table->integer('auto_post_interval')->default(10);
                
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('user_settings');
        
        Schema::table('users', function (Blueprint $table) {
            $columns = ['role', 'credits', 'total_credits_limit', 'is_active'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('websites', function (Blueprint $table) {
            if (Schema::hasColumn('websites', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('news_items', function (Blueprint $table) {
            if (Schema::hasColumn('news_items', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};