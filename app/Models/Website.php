<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    use HasFactory;

    // এই অংশটি যোগ করতে হবে
    protected $fillable = [
        'name',
        'url',
        'selector_container',
        'selector_title',
        'selector_image',
        'selector_content',
        'selector_time',
    ];

    // রিলেশনশিপ: একটি ওয়েবসাইটের অনেকগুলো নিউজ থাকতে পারে
    public function newsItems()
    {
        return $this->hasMany(NewsItem::class);
    }
}