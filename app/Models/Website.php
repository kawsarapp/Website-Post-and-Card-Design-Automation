<?php

namespace App\Models;

use App\Models\Scopes\UserScope; // স্কোপ ইমপোর্ট
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Website extends Model
{
    use HasFactory;

    // তোমার দেওয়া fillable রাখা হয়েছে
    protected $fillable = [
        'name',
        'url',
        'selector_container',
        'selector_title',
        'selector_image',
        'selector_content',
        'selector_time',
		'scraper_method',
    ];

    // ✅ স্কোপ বুট করা (তোমার প্রথম কোড থেকে যোগ করা হলো)
    protected static function booted()
    {
        static::addGlobalScope(new UserScope);

        // ডাটা সেভ হওয়ার সময় অটোমেটিক user_id বসবে
        static::creating(function ($website) {
            if (Auth::check()) {
                $website->user_id = Auth::id();
            }
        });
    }

    // রিলেশনশিপ: একটি ওয়েবসাইটের একজন ইউজার থাকে (তোমার প্রথম কোড অনুযায়ী)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // রিলেশনশিপ: একটি ওয়েবসাইটের অনেকগুলো নিউজ থাকতে পারে
    public function newsItems()
    {
        return $this->hasMany(NewsItem::class);
    }
}
