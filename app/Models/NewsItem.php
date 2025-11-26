<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsItem extends Model
{
    use HasFactory;

    // এই অংশটিই সমস্যা করছিল, এখন ঠিক করা হলো
    protected $fillable = [
        'website_id',
        'title',
        'thumbnail_url',
        'content',
        'original_link', // ✅ এই লাইনটি আগে ছিল না
        'published_at',
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }
}