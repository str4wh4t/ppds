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
        $hours = $weekMonitor->workload_hours;
        if ($hours >= 80) {
            // $weekMonitor->user->notify(new WorkloadNotification($weekMonitor));
            // $usersWithPermission = User::permission('get-notifs')->get();
            // Notification::send($usersWithPermission, new WorkloadNotification($weekMonitor));
        }
    }
}
