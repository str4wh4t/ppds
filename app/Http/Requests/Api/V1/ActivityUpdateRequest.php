<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Activity\UpdateRequest as ActivityUpdateRequestBase;
use App\Models\Activity;

/**
 * Validasi body sama dengan {@see ActivityUpdateRequestBase}; otorisasi API mengizinkan role `system`.
 */
class ActivityUpdateRequest extends ActivityUpdateRequestBase
{
    public function authorize(): bool
    {
        /** @var Activity|null $activity */
        $activity = $this->route('activity');
        if (! $activity instanceof Activity) {
            return false;
        }

        $user = $this->user();
        if ((int) $activity->user_id !== (int) $user->id && ! $user->hasRole('system')) {
            return false;
        }

        if ($activity->is_overdue_checkout) {
            return false;
        }

        return true;
    }
}
