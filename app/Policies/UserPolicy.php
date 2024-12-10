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
            $adminUnitIds = $user->adminUnits->pluck('id')->toArray();
            if ($request->student_unit_id) { // << jika ingin menambahkan data student
                if (in_array($request->student_unit_id, $adminUnitIds)) {
                    return true;
                }
            }
            /** UNTUK DOSEN DIMATIKAN KARENA PADA DOSEN BISA DI ASIGN KE BANYAK UNIT */
            // if ($request->dosen_units) { // << jika ingin menambahkan data dosen
            //     $dosen_units = $request->dosen_units;
            //     $ids = array_map(function ($dosen_unit) {
            //         return $dosen_unit['id'];
            //     }, $dosen_units);
            //     $isSubset = empty(array_diff($ids, $adminUnitIds)); // Jika true, maka $subset berada dalam $superset.
            //     if ($isSubset) {
            //         return true;
            //     }
            // }
        }

        return false;
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

        // jika ingin mengubah data student
        if ($user->hasRole('admin_prodi')) {
            $request = app(Request::class);
            if ($model->hasRole('student')) {
                if ($request->student_unit_id == $model->student_unit_id) { // <== untuk mencegah memindah data student ke unit lain
                    $adminUnitIds = $user->adminUnits->pluck('id')->toArray();
                    if (in_array($model->student_unit_id, $adminUnitIds)) {
                        return true;
                    }
                }
            }
            // if ($model->hasRole('dosen')) { // << jika ingin mengedit data dosen
            //     return false;
            // }
            // if ($model->hasRole('kaprodi')) { // << jika ingin mengedit data kaprodi
            //     return false;
            // }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Jika user yang sedang login memiliki role "system"
        if ($user->hasRole('system')) {
            // User dengan role "system" tidak boleh menghapus sesama pengguna dengan role "system"
            if (!$model->hasRole('system')) {
                return true;
            }
        }

        if ($user->hasRole('admin_prodi')) {
            $adminUnitIds = $user->adminUnits->pluck('id')->toArray();
            if ($model->hasRole('student')) { // << jika ingin menghapus data student
                if (in_array($model->student_unit_id, $adminUnitIds)) {
                    return true;
                }
            }
            // if ($model->hasRole('dosen')) { // << jika ingin menghapus data dosen
            //     return false;
            // }
            // if ($model->hasRole('kaprodi')) { // << jika ingin menghapus data kaprodi
            //     return false;
            // }
        }

        // Selain itu, izinkan penghapusan jika logika di atas tidak dilanggar

        return false;
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
