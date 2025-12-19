<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\UserScope;

class NewsItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'website_id',
        'title',
        'ai_title',
        'content',
        'ai_content',
        'original_link',
        'thumbnail_url',
        'published_at',
        'status',        // draft, published, processing, failed
        'is_posted',
        'wp_post_id',
        'posted_at',
        'error_message',
        'is_rewritten',
		'fb_status',
        'fb_error',
        'tg_status',
        'tg_error'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'posted_at' => 'datetime',
        'is_posted' => 'boolean',
        'is_rewritten' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new UserScope);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function website()
    {
        return $this->belongsTo(Website::class);
    }
	
	
	public function getLiveUrlAttribute()
    {
        $settings = $this->user->settings ?? null;
        if (!$settings) return null;

        if ($this->wp_post_id && $settings->wp_url) {
            return rtrim($settings->wp_url, '/') . '/?p=' . $this->wp_post_id;
        }

        if ($settings->post_to_laravel && $settings->laravel_site_url) {
            $id = $this->wp_post_id ?? $this->id; 
            $prefix = $settings->laravel_route_prefix ?? 'news';
            $prefix = trim($prefix, '/');

            return rtrim($settings->laravel_site_url, '/') . '/' . $prefix . '/' . $id;
        }

        return null;
    }
	
	
}