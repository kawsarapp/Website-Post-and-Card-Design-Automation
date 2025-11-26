<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'description',
        'credits_change',
        'balance_after'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}