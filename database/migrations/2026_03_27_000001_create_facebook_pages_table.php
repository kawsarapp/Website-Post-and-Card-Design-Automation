<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    public function up(): void
    {
        // ১. নতুন facebook_pages টেবিল তৈরি
        Schema::create('facebook_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('page_name')->default('My Page');
            $table->string('page_id');
            $table->text('access_token'); // encrypted
            $table->boolean('is_active')->default(true);
            $table->boolean('comment_link')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('test_status')->nullable(); // 'connected' | 'failed'
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // ২. পুরনো user_settings থেকে data migrate করা
        $oldPages = DB::table('user_settings')
            ->whereNotNull('fb_page_id')
            ->where('fb_page_id', '!=', '')
            ->whereNotNull('fb_access_token')
            ->where('fb_access_token', '!=', '')
            ->get();

        foreach ($oldPages as $setting) {
            try {
                // Decrypt the old token (it was stored encrypted)
                $decryptedToken = decrypt($setting->fb_access_token);
                // Re-encrypt for new table
                $reEncrypted = encrypt($decryptedToken);

                DB::table('facebook_pages')->insert([
                    'user_id'      => $setting->user_id,
                    'page_name'    => 'My Facebook Page',
                    'page_id'      => $setting->fb_page_id,
                    'access_token' => $reEncrypted,
                    'is_active'    => $setting->post_to_fb ?? true,
                    'comment_link' => $setting->fb_comment_link ?? false,
                    'test_status'  => 'connected',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            } catch (\Exception $e) {
                // যদি decrypt না হয়, raw value দিয়েই চেষ্টা করি
                try {
                    DB::table('facebook_pages')->insert([
                        'user_id'      => $setting->user_id,
                        'page_name'    => 'My Facebook Page',
                        'page_id'      => $setting->fb_page_id,
                        'access_token' => encrypt($setting->fb_access_token),
                        'is_active'    => $setting->post_to_fb ?? true,
                        'comment_link' => $setting->fb_comment_link ?? false,
                        'test_status'  => null,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                } catch (\Exception $e2) {
                    // Skip this record if encryption fails
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_pages');
    }
};
