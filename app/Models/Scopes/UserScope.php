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
        if (!Auth::check()) return;

        $user = Auth::user();
        $table = $model->getTable();

        if ($user->role === 'super_admin') {
            // Super admin সব দেখবে
            return;
        }

        if (in_array($user->role, ['staff', 'reporter'])) {
            if ($table === 'websites') {
                // Staff শুধু তাদের assigned websites দেখবে (user_website pivot)
                // অথবা তাদের admin-এর owned websites
                $builder->where(function ($q) use ($user) {
                    $q->where('user_id', $user->parent_id)
                      ->orWhereHas('users', function ($q2) use ($user) {
                          $q2->where('users.id', $user->id);
                      });
                });
            } else {
                // অন্য সব table (news_items etc): admin-এর pool বা নিজের staff_id
                $builder->where(function ($q) use ($table, $user) {
                    $q->where($table . '.user_id', $user->parent_id)
                      ->orWhere($table . '.staff_id', $user->id);
                });
            }
        } else {
            // Admin: নিজের data
            $builder->where($table . '.user_id', $user->id);
        }
    }
}