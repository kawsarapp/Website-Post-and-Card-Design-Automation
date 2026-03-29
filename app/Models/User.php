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
		'parent_id',
        'credits', 
        'total_credits_limit', 
        'daily_post_limit',
        'is_active',
        'staff_limit',
        'permissions',
        'last_login_at',
        'author_signature',
        'signature_placement',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
		'permissions' => 'array',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    // ==========================================
    // 🔥 RELATIONSHIPS
    // ==========================================

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function newsItems()
    {
        return $this->hasMany(NewsItem::class);
    }

    public function accessibleWebsites()
    {
        return $this->belongsToMany(Website::class, 'user_website', 'user_id', 'website_id');
    }
    
    public function creditHistories()
    {
        return $this->hasMany(CreditHistory::class)->latest();
    }

    public function websites() 
    { 
        return $this->hasMany(Website::class); 
    }

    // ইউজারের সকল Facebook Pages
    public function facebookPages()
    {
        return $this->hasMany(FacebookPage::class);
    }

    // ==========================================
    // 🔥 HELPER FUNCTIONS
    // ==========================================

    public function hasDailyLimitRemaining()
    {
        if ($this->role === 'super_admin') return true;

        $todayPosts = $this->newsItems()
            ->where('is_posted', true)
            ->whereDate('posted_at', now())
            ->count();

        return $todayPosts < ($this->daily_post_limit ?? 10);
    }

    public function hasCredits()
    {
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
	
	public function reporters()
	{
		return $this->hasMany(User::class, 'parent_id');
	}

	public function parent()
	{
		return $this->belongsTo(User::class, 'parent_id');
	}
	
	public function hasPermission($permission)
    {
        if ($this->role === 'super_admin') return true;
        return is_array($this->permissions) && in_array($permission, $this->permissions);
    }
}
