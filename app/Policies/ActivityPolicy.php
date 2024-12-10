<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ActivityPolicy
{

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('student')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Activity $model): bool
    {
        //
        return $user->id === $model->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Activity $model): bool
    {
        //
        return $user->id === $model->user_id;
    }

    public function permitActivity(User $user, Activity $model): bool
    {
        //
        if ($user->hasRole('kaprodi')) {
            return true;
        }

        return false;
    }
}
