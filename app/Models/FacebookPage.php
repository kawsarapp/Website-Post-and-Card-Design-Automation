<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'page_name',
        'page_id',
        'access_token',
        'is_active',
        'is_studio_default',
        'comment_link',
        'last_tested_at',
        'test_status',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'is_studio_default' => 'boolean',
        'comment_link'      => 'boolean',
        'access_token'      => 'encrypted',
        'last_tested_at'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
