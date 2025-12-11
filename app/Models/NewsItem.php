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
        'is_rewritten'   // 🔥 এটি যোগ করা হয়েছে (যাতে ডাটাবেসে সেভ হয়)
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'posted_at' => 'datetime',
        'is_posted' => 'boolean',
        'is_rewritten' => 'boolean', // 🔥 কাস্টিং যোগ করা হয়েছে
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
}