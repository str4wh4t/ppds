<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    private function isSameCreationChannel(Activity $model): bool
    {
        $expectedChannel = request()->is('api/*') ? 'api' : 'web';

        return $model->created_via === $expectedChannel;
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
    public function update(User $user, Activity $model): bool
    {
        if (! $user->hasRole('student')) {
            return true;
        }

        if ($user->id !== $model->user_id) {
            return false;
        }

        if ((bool) $model->is_overdue_checkout) {
            return false;
        }

        return $this->isSameCreationChannel($model);
    }

    /**
     * Determine whether the user can checkout the model.
     */
    public function checkout(User $user, Activity $model): bool
    {
        if (! $user->hasRole('student')) {
            return true;
        }

        if ($user->id !== $model->user_id) {
            return false;
        }

        if ((bool) $model->is_overdue_checkout) {
            return false;
        }

        return $this->isSameCreationChannel($model);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Student: hanya activity sendiri; boleh meskipun overdue.
     * User selain student: boleh menghapus activity milik user lain (mis. admin lewat API).
     */
    public function delete(User $user, Activity $model): bool
    {
        if (! $user->hasRole('student')) {
            return true;
        }

        return (int) $user->id === (int) $model->user_id;
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
