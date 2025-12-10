<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'role', 
        'credits', 
        'total_credits_limit', 
        'daily_post_limit',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ==========================================
    // 🔥 RELATIONSHIPS
    // ==========================================

    // ইউজারের সেটিংস
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    // ইউজারের নিউজ আইটেম
    public function newsItems()
    {
        return $this->hasMany(NewsItem::class);
    }

    // ইউজারের এক্সেস থাকা ওয়েবসাইট
    public function accessibleWebsites()
    {
        return $this->belongsToMany(Website::class, 'user_website');
    }
    
    // ইউজারের ক্রেডিট হিস্ট্রি
    public function creditHistories()
    {
        return $this->hasMany(CreditHistory::class)->latest();
    }

    // (Optional) যদি সরাসরি ওয়েবসাইট রিলেশন থাকে
    public function websites() 
    { 
        return $this->hasMany(Website::class); 
    }

    // ==========================================
    // 🔥 HELPER FUNCTIONS (LIMIT & CREDIT)
    // ==========================================

    /**
     * ১. আজকের পোস্ট লিমিট বাকি আছে কিনা চেক করা
     */
    public function hasDailyLimitRemaining()
    {
        // সুপার এডমিনের কোনো লিমিট নেই
        if ($this->role === 'super_admin') return true;

        // আজকের পোস্ট কাউন্ট করা (যেকোনো পোস্ট যা পাবলিশ হয়েছে)
        $todayPosts = $this->newsItems()
            ->where('is_posted', true)
            ->whereDate('posted_at', now()) // আজকের তারিখ
            ->count();

        // যদি আজকের পোস্ট < দৈনিক লিমিট হয়, তবে সত্য
        return $todayPosts < ($this->daily_post_limit ?? 10); // ডিফল্ট ১০
    }

    /**
     * ২. অ্যাকাউন্টে পর্যাপ্ত ক্রেডিট আছে কিনা চেক করা
     */
    public function hasCredits()
    {
        // সুপার এডমিনের আনলিমিটেড ক্রেডিট
        if ($this->role === 'super_admin') return true;
        
        return $this->credits > 0;
    }
	
		
		


    public function getTodaysPostCountAttribute()
    {
        return $this->newsItems()
            ->withoutGlobalScopes()
            ->where('is_posted', true)
            ->where(function($q) {
                $q->whereDate('posted_at', \Carbon\Carbon::now())
                  ->orWhereDate('updated_at', \Carbon\Carbon::now());
            })
            ->count();
    }
	
	
	
	
}