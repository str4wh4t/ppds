<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Permission $model): bool
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Permission $model): bool
    {
        //
        // Jika user yang sedang login memiliki role "system"
        if (!$user->hasRole('system')) {
            return false;
        }

        // Selain itu, izinkan penghapusan jika logika di atas tidak dilanggar
        return true;
    }
}
