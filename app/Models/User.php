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

    // ইউজারের সেটিংস
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }
    
    public function websites() { return $this->hasMany(Website::class); }
    public function newsItems() { return $this->hasMany(NewsItem::class); }
}