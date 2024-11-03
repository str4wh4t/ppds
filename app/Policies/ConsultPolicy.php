<?php

namespace App\Policies;

use App\Models\Consult;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConsultPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function index(User $user): bool
    {
        //
        if ($user->hasRole(['system', 'admin_fakultas'])) {
            return true;
        }

        // if ($user->id == $model->user_id) {
        //     return true;
        // }
        return false;
    }

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
    public function update(User $user, Consult $model): bool
    {
        //
        if ($user->id == $model->user_id) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Consult $model): bool
    {
        // 
        if ($user->id == $model->user_id) {
            return true;
        }
        return false;
    }
}
