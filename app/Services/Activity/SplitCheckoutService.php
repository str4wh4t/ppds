<?php

namespace App\Services\Activity;

use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Checkout dengan aturan: satu record tidak melewati tengah malang;
 * batas akhir segmen = 00:00:00 di hari kalender berikutnya (bukan 23:59:59).
 * Record lanjutan mulai pada timestamp yang sama (00:00:00 hari baru).
 */
class SplitCheckoutService
{
    /**
     * @return array{ primary: Activity, additional: Collection<int, Activity> }
     */
    public function execute(Activity $activity, Carbon $targetFinish): array
    {
        $start = Carbon::parse($activity->start_date);

        if ($targetFinish->lte($start)) {
            throw new \InvalidArgumentException('Waktu selesai tidak boleh kurang dari atau sama dengan waktu mulai.');
        }

        $baseAttributes = $this->baseAttributesFrom($activity);
        $cursor = $start->copy();
        $additional = collect();
        $isFirst = true;

        while ($cursor->lt($targetFinish)) {
            // Batas "akhir hari" = 00:00:00 di awal hari kalender berikutnya.
            $nextMidnight = $cursor->copy()->startOfDay()->addDay();
            $segmentEnd = $targetFinish->lte($nextMidnight)
                ? $targetFinish->copy()
                : $nextMidnight->copy();

            $seconds = $cursor->diffInSeconds($segmentEnd);
            if ($seconds <= 0) {
                break;
            }

            $timeSpend = $this->secondsToTimeSpend($seconds);
            $weekGroupId = $this->weekGroupIdFromStart($cursor);

            if ($isFirst) {
                $activity->update([
                    'end_date' => $segmentEnd,
                    'time_spend' => $timeSpend,
                    'week_group_id' => $weekGroupId,
                    'is_overdue_checkout' => false,
                ]);
                $isFirst = false;
            } else {
                $additional->push(Activity::create(array_merge($baseAttributes, [
                    'start_date' => $cursor,
                    'end_date' => $segmentEnd,
                    'time_spend' => $timeSpend,
                    'week_group_id' => $weekGroupId,
                    'is_overdue_checkout' => false,
                    'is_generated' => 0,
                ])));
            }

            if ($segmentEnd->gte($targetFinish)) {
                break;
            }

            // Lanjutan dimulai tepat di `end_date` segmen sebelumnya (00:00:00 hari berikutnya).
            $cursor = $segmentEnd->copy();
        }

        return [
            'primary' => $activity->fresh(),
            'additional' => $additional,
        ];
    }

    private function baseAttributesFrom(Activity $activity): array
    {
        return [
            'user_id' => $activity->user_id,
            'name' => $activity->name,
            'type' => $activity->type,
            'description' => $activity->description,
            'unit_stase_id' => $activity->unit_stase_id,
            'stase_location_id' => $activity->stase_location_id,
            'stase_id' => $activity->stase_id,
            'location_id' => $activity->location_id,
            'latitude' => $activity->latitude,
            'longitude' => $activity->longitude,
            'checkin_photo_path' => $activity->checkin_photo_path,
            'checkout_photo_path' => $activity->checkout_photo_path,
            'checkout_latitude' => $activity->checkout_latitude,
            'checkout_longitude' => $activity->checkout_longitude,
            'dosen_user_id' => $activity->dosen_user_id,
            'is_allowed' => $activity->is_allowed,
            'created_via' => $activity->created_via,
            'device_info' => $activity->device_info,
            'is_generated' => 0,
        ];
    }

    private function weekGroupIdFromStart(Carbon $start): int
    {
        $yearIso = $start->isoWeekYear;
        $weekIso = $start->isoWeek;

        return (int) ($yearIso.$weekIso);
    }

    private function secondsToTimeSpend(int $timeSpendInSeconds): string
    {
        $hours = floor($timeSpendInSeconds / 3600);
        $minutes = floor(($timeSpendInSeconds % 3600) / 60);
        $seconds = $timeSpendInSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
