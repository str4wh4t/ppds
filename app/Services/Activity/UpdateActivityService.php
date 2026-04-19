<?php

namespace App\Services\Activity;

use App\DTOs\Activity\UpdateActivityData;
use App\Models\Activity;
use App\Models\StaseLocation;
use App\Models\UnitStase;
use App\Models\User;
use App\Models\WeekMonitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Memperbarui activity; rentang waktu mengikuti validasi request (selesai tidak sebelum mulai, boleh sama).
 * User selain `student` yang mengubah waktu: `is_overdue_checkout` diselaraskan dengan apakah masih “open check-in” dan ≥24 jam setelah update.
 */
class UpdateActivityService
{
    public function execute(Activity $activity, UpdateActivityData $data, ?User $actingUser = null): Activity
    {
        return DB::transaction(function () use ($activity, $data, $actingUser) {
            $activity->loadMissing('user');

            $startDate = Carbon::parse($data->date . ' ' . $data->startTime);
            $endDate = Carbon::parse($data->date . ' ' . $data->finishTime);
            $timeSpendInSeconds = $startDate->diffInSeconds($endDate);

            $hours = (int) floor($timeSpendInSeconds / 3600);
            $minutes = (int) floor(($timeSpendInSeconds % 3600) / 60);
            $seconds = $timeSpendInSeconds % 60;
            $timeSpend = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            $yearIso = $startDate->isoWeekYear;
            $weekIso = $startDate->isoWeek;
            $newWeekGroupId = (int) ($yearIso . $weekIso);

            $changeToAllow = false;
            [$prev_activity_hours] = explode(':', $activity->time_spend);
            if ((int) $prev_activity_hours !== $hours) {
                $weekMonitor = WeekMonitor::where('user_id', $activity->user_id)
                    ->where('week_group_id', $activity->week_group_id)
                    ->first();
                if ($weekMonitor) {
                    $updatedWorkloadHours = ($weekMonitor->workload_hours - (int) $prev_activity_hours) + $hours;
                    if ($updatedWorkloadHours > 80) {
                        if ((int) $activity->is_allowed === 1) {
                            throw new \Exception('Workload exceeded');
                        }
                    } else {
                        if ((int) $activity->is_allowed === 0) {
                            $changeToAllow = true;
                        } else {
                            Activity::where('is_allowed', 0)
                                ->where('user_id', $activity->user_id)
                                ->where('week_group_id', $activity->week_group_id)
                                ->update(['is_allowed' => 1]);
                        }
                    }
                }
            }

            $unitStaseId = null;
            $staseLocationId = null;

            if ($data->type === 'nonjaga') {
                $unitStase = UnitStase::where('stase_id', $data->staseId)
                    ->where('unit_id', $activity->user->student_unit_id)
                    ->firstOrFail();

                $unitStaseId = $unitStase->id;

                $staseLocation = StaseLocation::where('stase_id', $data->staseId)
                    ->where('location_id', $data->locationId)
                    ->firstOrFail();

                $staseLocationId = $staseLocation->id;
            }

            $attributes = [
                'name' => $data->name,
                'type' => $data->type,
                'unit_stase_id' => $data->type === 'jaga' ? null : $unitStaseId,
                'stase_id' => $data->type === 'jaga' ? null : $data->staseId,
                'stase_location_id' => $data->type === 'jaga' ? null : $staseLocationId,
                'location_id' => $data->type === 'jaga' ? null : $data->locationId,
                'dosen_user_id' => $data->dosenUserId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'time_spend' => $timeSpend,
                'description' => $data->description,
                'week_group_id' => $newWeekGroupId,
                'is_allowed' => (int) $activity->is_allowed === 0 ? ($changeToAllow ? 1 : 0) : 1,
            ];

            if ($actingUser !== null && ! $actingUser->hasRole('student')) {
                $attributes['is_overdue_checkout'] = $this->isOverdueOpenCheckInAfterUpdate(
                    $activity,
                    $startDate,
                    $endDate,
                    $timeSpend
                );
            }

            $activity->update($attributes);

            return $activity->fresh();
        });
    }

    /**
     * Setelah update: activity non-generated, `time_spend` nol, mulai = selesai, dan ≥24 jam sejak mulai.
     */
    private function isOverdueOpenCheckInAfterUpdate(
        Activity $activity,
        Carbon $startDate,
        Carbon $endDate,
        string $timeSpend
    ): bool {
        if ((int) $activity->is_generated !== 0) {
            return false;
        }

        if ($timeSpend !== '00:00:00') {
            return false;
        }

        if (! $startDate->equalTo($endDate)) {
            return false;
        }

        return $startDate->diffInHours(now()) >= 24;
    }
}
