<?php

namespace App\Listeners;

use App\Events\WorkloadExceeded;
use App\Models\Activity;
use App\Models\User;
use App\Models\WeekMonitor;
use App\Notifications\WorkloadNotification;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class WorkloadMonitor implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(WorkloadExceeded $event): void
    {
        $weekMonitor = $event->weekMonitor;
        $activity = $event->activity;
        $hours = $weekMonitor->workload_hours;
        if ($hours >= 80) {

            // list($hours, $minutes, $seconds) = explode(':', $activity->time_spend);
            // $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
            // $workhours = floor($totalSeconds / 3600);
            // if ($activity->wasRecentlyCreated) {
            //     Log::info('Activity created');
            //     $weekMonitor->workload_hours_not_allowed = $weekMonitor->workload_hours_not_allowed + $workhours;
            // } else {
            //     Log::info('Activity updated');
            // }
            // $weekMonitor->save();
            // $activity->is_allowed = false;
            // $activity->saveQuietly();
            // $changes = $activity->getChanges();
            // Log::info('Activity changes: ' . json_encode($changes));


            // $weekMonitor->user->notify(new WorkloadNotification($weekMonitor));
            // $usersWithPermission = User::permission('get-notifs')->get();
            // Notification::send($usersWithPermission, new WorkloadNotification($weekMonitor));
        }
    }
}
