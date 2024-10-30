<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
        if ($user->hasRole(['system', 'admin_fakultas'])) {
            return true;
        }

        if ($user->hasRole('admin_prodi')) {
            $request = app(Request::class);
            if ($request->student_unit_id) {
                $adminUnitIds = $user->adminUnits->pluck('id')->toArray();
                if (!in_array($request->student_unit_id, $adminUnitIds)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        //
        if ($user->hasRole(['system', 'admin_fakultas'])) {
            return true;
        }

        if (!$user->hasRole('system')) {
            // User dengan role selain "system" tidak boleh mengedit pengguna dengan role "system"
            if ($model->hasRole('system')) {
                return false;
            }
        }

        // jika ingin mengubah data student
        if ($user->hasRole('admin_prodi')) {
            $request = app(Request::class);
            if ($model->hasRole('student')) {
                if ($request->student_unit_id != $model->student_unit_id) {
                    return false;
                }
                $adminUnitIds = $user->adminUnits->pluck('id')->toArray();
                if (!in_array($request->student_unit_id, $adminUnitIds)) {
                    return false;
                }
            }

            if ($model->hasRole('dosen')) {
                return false;
            }

            if ($model->hasRole('kaprodi')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Jika user yang sedang login memiliki role "system"
        if ($user->hasRole('system')) {
            // User dengan role "system" tidak boleh menghapus sesama pengguna dengan role "system"
            if ($model->hasRole('system')) {
                return false;
            }
        }

        // Pengguna yang tidak memiliki role "system" tidak boleh menghapus pengguna dengan role "system"
        if (!$user->hasRole('system')) {
            if ($model->hasRole('system')) {
                return false;
            }
        }

        if ($user->hasRole('admin_prodi')) {
            if ($model->hasRole('student')) { // << jika ingin menghapus data student
                $adminUnitIds = $user->adminUnits->pluck('id')->toArray();
                if (!in_array($model->student_unit_id, $adminUnitIds)) {
                    return false;
                }
            }
            if ($model->hasRole('dosen')) { // << jika ingin menghapus data dosen
                return false;
            }
            if ($model->hasRole('kaprodi')) { // << jika ingin menghapus data kaprodi
                return false;
            }
        }

        // Selain itu, izinkan penghapusan jika logika di atas tidak dilanggar

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        //
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        //
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function resetPassword(User $user, User $model): bool
    {
        //
        return true;
    }
}
