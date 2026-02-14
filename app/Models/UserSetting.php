<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'brand_name',
        'logo_url',
        'default_theme_color',
        'wp_url',
        'wp_username',
        'wp_app_password',
        'telegram_channel_id',
        'is_auto_posting',
        'auto_post_interval',
        'last_auto_post_at',
		'allowed_templates', 
		'default_template',
		'scraper_method',
		'category_mapping',
		'design_preferences',
		'fb_page_id',
		'fb_access_token',
		'telegram_bot_token',
		'telegram_channel_id',
		'post_to_fb',
        'post_to_telegram',
		'laravel_site_url',   // লারাভেল সাইটের লিংক
		'laravel_api_token',  // সিকিউরিটি টোকেন
		'post_to_laravel',
		'fb_comment_link',
		'proxy_host',
		'proxy_port',
		'proxy_username',
		'proxy_password',

    ];


	// ✅ JSON কে Array তে কনভার্ট করা
    protected $casts = [
        'allowed_templates' => 'array',
        'is_auto_posting' => 'boolean',
		'category_mapping' => 'array',
		'design_preferences' => 'array',
		'post_to_fb' => 'boolean',
        'post_to_telegram' => 'boolean',
		'post_to_laravel' => 'boolean',
		'wp_app_password' => 'encrypted',
        'fb_access_token' => 'encrypted',
        'telegram_bot_token' => 'encrypted',
        'laravel_api_token' => 'encrypted',
    ];

    // ✅ টেমপ্লেট লিস্ট (Master List)
    public const AVAILABLE_TEMPLATES = [
        'ntv'           => '🟩 NTV News',
        'rtv'           => '🟥 RTV News',
        'dhakapost'     => '🟦 Dhaka Post',
        'todayevents'   => '🟪 Today Events',
		'todayeventsSingle'   => '🟪 Today Events Single',
		'BanglaLiveNews' => 'Bangla Live News',
		'BanglaLiveNews1' => 'Bangla Live News 1',
		'Jaijaidin1' => 'Jaijaidin 1',
		'Jaijaidin2' => 'Jaijaidin 2',
		'Jaijaidin3' => 'Jaijaidin 3',
		'Jaijaidin4' => 'Jaijaidin 4',
		'ShotterKhoje' => 'Shotter Khoje',
		'jonomot' => 'jonomot',
		'Bangladeshmail24' => 'Bangladeshmail24',
		'WatchBangladesh' => 'WatchBangladesh',
		'TodayEventsDualFrame' => 'TodayEventsDualFrame',
		'todayeventsSingle1' => 'todayeventsSingle1',

		
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}