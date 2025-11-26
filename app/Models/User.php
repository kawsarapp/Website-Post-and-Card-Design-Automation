<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'credits', 'total_credits_limit', 'is_active'
    ];

	
	public function hasDailyLimitRemaining()
    {
        
		$todayUsage = $this->hasMany(CreditHistory::class)
            ->whereDate('created_at', today())
            ->where('credits_change', '<', 0)
            ->count();

        return $todayUsage < $this->daily_post_limit;
    }
	
	
    public function accessibleWebsites()
    {
        return $this->belongsToMany(Website::class, 'user_website');
    }
    
	public function creditHistories()
    {
        return $this->hasMany(CreditHistory::class)->latest();
    }
	

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }
    
    public function websites() { return $this->hasMany(Website::class); }
    public function newsItems() { return $this->hasMany(NewsItem::class); }
}