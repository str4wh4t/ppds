<?php

namespace App\Events;

use App\Models\WeekMonitor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkloadExceeded
{
    public $weekMonitor;

    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(WeekMonitor $weekMonitor)
    {
        $this->weekMonitor = $weekMonitor;
    }
}
