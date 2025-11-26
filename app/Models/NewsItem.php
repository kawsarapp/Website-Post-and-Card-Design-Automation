<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class NewsItem extends Model
{
    use HasFactory;

    // তোমার দেওয়া দুইটি fillable একত্র করা হয়েছে—কোনো কিছু বাদ দেওয়া হয়নি
    protected $fillable = [
		'user_id',
        'user_id',
        'website_id',
        'title',
        'thumbnail_url',
        'content',
        'original_link',
        'published_at',
        'rewritten_content',
        'is_posted',
		'is_queued',
        'wp_post_id'
		
    ];

    // প্রথম কোড থেকে booted() যোগ করা হলো—একটুও পরিবর্তন করা হয়নি
    protected static function booted()
    {
        static::addGlobalScope(new UserScope);

        static::creating(function ($item) {
            if (Auth::check()) {
                $item->user_id = Auth::id();
            }
        });
    }

    // দুই কোডে থাকা রিলেশন—একদম 그대로 রাখা হয়েছে
    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
