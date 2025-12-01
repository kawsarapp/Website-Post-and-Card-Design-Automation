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

    ];


	// ✅ JSON কে Array তে কনভার্ট করা
    protected $casts = [
        'allowed_templates' => 'array',
        'is_auto_posting' => 'boolean',
		'category_mapping' => 'array',
		'design_preferences' => 'array',
		'post_to_fb' => 'boolean',
        'post_to_telegram' => 'boolean',
    ];

    // ✅ টেমপ্লেট লিস্ট (Master List)
    public const AVAILABLE_TEMPLATES = [
        'ntv'           => '🟩 NTV News',
        'rtv'           => '🟥 RTV News',
        'dhakapost'     => '🟦 Dhaka Post',
        'dhakapost_new' => '⬛ Dhaka Post (Dark)',
        'todayevents'   => '🟪 Today Events',
        'modern_left'   => '🔵 Modern Left',
        'top_heavy'     => '🏏 Sports/Top',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}