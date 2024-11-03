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
        'year',
        'week',
        'workload',
        'workload_hours',
        'workload_as_seconds',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::addGlobalScope('latest', function ($query) {
            $query->orderBy('user_id')->orderBy('week', 'desc');
        });
    }
}
