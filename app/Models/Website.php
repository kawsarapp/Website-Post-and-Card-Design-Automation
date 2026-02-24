<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'selector_container',
        'selector_title',
        'selector_image',
        'selector_content',
        'selector_time',
        'scraper_method',
        'user_id', 
    ];

    protected static function booted()
    {
        static::addGlobalScope(new UserScope);

        static::creating(function ($website) {
            if (Auth::check()) {
                $website->user_id = Auth::id();
            }
        });
    }


    public function users()
        {
            return $this->belongsToMany(User::class, 'user_website', 'website_id', 'user_id');
        }

    /**
     * মূল মালিক (Creator)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * এই ওয়েবসাইটের আন্ডারে থাকা নিউজ আইটেমগুলো
     */
    public function newsItems()
    {
        return $this->hasMany(NewsItem::class);
    }
}