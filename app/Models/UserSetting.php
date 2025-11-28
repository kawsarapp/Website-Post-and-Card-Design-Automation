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
    ];


	// ✅ JSON কে Array তে কনভার্ট করা
    protected $casts = [
        'allowed_templates' => 'array',
        'is_auto_posting' => 'boolean',
		'category_mapping' => 'array',
    ];

    // ✅ টেমপ্লেট লিস্ট (Master List)
    public const AVAILABLE_TEMPLATES = [
        'dhaka_post_card' => '🟦 Dhaka Post Style',
        'rtv_news_card' => '🟥 RTV News Style',
        'viral_bold' => '⚡ Viral Bold',
        'quote_pro' => '❝ Quote Statement',
        'classic' => '📺 Classic Studio',
        'modern_split' => '🔲 Modern Split',
        'bold_overlay' => '🔴 Breaking Red',
        'broadcast_tv' => '📡 TV Broadcast',
        'insta_modern' => '📸 Insta Square',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}