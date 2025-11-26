<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class UserScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // সুপার এডমিন সব দেখতে পাবে, সাধারণ ইউজার শুধু নিজের ডাটা দেখবে
        if (Auth::check()) {
            if (Auth::user()->role !== 'super_admin') {
                $builder->where('user_id', Auth::id());
            }
        }
    }
}