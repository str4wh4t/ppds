<?php

namespace App\Events;

use App\Models\Activity;
use App\Models\WeekMonitor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkloadExceeded
{
    public $weekMonitor;
    public $activity;

    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(WeekMonitor $weekMonitor, Activity $activity)
    {
        $this->weekMonitor = $weekMonitor;
        $this->activity = $activity;
    }
}
