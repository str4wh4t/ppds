<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;

class UnitPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
        // if (!$user->hasRole(['system', 'admin_fakultas'])) {
        //     return false;
        // }

        // Selain itu, izinkan penghapusan jika logika di atas tidak dilanggar
        // return true;
        if ($user->hasRole(['system', 'admin_fakultas'])) {
            return true;
        }

        // User dengan role selain "system" tidak diizinkan membuat unit
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Unit $unit): bool
    {
        //
        if ($user->hasRole(['system', 'admin_fakultas'])) {
            return true;
        }

        if ($user->hasRole('admin_prodi')) {

            $request = app(Request::class);
            $unit_admins = $request->unit_admins;
            if (!empty($unit_admins)) {
                $newIds = array_map(function ($unit_admin) {
                    return $unit_admin['id'];
                }, $unit_admins);

                $oldIds = $unit->unitAdmins->pluck('id')->toArray();

                sort($newIds);
                sort($oldIds);

                if ($newIds === $oldIds) {
                    return true;
                }
            }
        }


        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Unit $unit): bool
    {
        //
        if ($user->hasRole(['system', 'admin_fakultas'])) {
            return true;
        }
        return false;
    }

    public function processGuideline(User $user, Unit $unit): bool
    {
        //
        if ($user->hasRole(['system', 'admin_fakultas'])) {
            return true;
        }

        if ($user->hasRole('admin_prodi')) {
            $adminUnits = $user->adminUnits->pluck('id');
            if ($adminUnits->contains($unit->id)) {
                return true;
            }
        }
        return false;
    }

    public function processSchedule(User $user, Unit $unit): bool
    {
        //
        if ($user->hasRole(['system', 'admin_fakultas'])) {
            return true;
        }

        if ($user->hasRole('admin_prodi')) {
            $adminUnits = $user->adminUnits->pluck('id');
            if ($adminUnits->contains($unit->id)) {
                return true;
            }
        }
        return false;
    }
}
