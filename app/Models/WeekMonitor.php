<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeekMonitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_group_id',
        'workload',
        'workload_as_seconds',
    ];
}
