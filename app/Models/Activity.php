<?php

namespace App\Models;

use App\Observers\ActivityObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([ActivityObserver::class])]
class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'time_spend',
        'description',
        'is_approved',
        'approved_by',
        'approved_at',
        'unit_stase_id',
        'stase_location_id',
        'stase_id',
        'location_id',
        'dosen_user_id',
        'week_group_id',
        'is_generated',
        'is_allowed',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dosenUser()
    {
        return $this->belongsTo(User::class, 'dosen_user_id', 'id');
    }

    public function weekMonitor()
    {
        return $this->belongsTo(WeekMonitor::class, 'week_group_id', 'week_group_id');
    }

    public function unitStase()
    {
        return $this->belongsTo(UnitStase::class);
    }

    public function stase()
    {
        return $this->belongsTo(Stase::class);
    }

    public function staseLocation()
    {
        return $this->belongsTo(StaseLocation::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    protected static function booted()
    {
        static::addGlobalScope('generated', function ($query) {
            $query->where('is_generated', 0)
                // ->where('is_allowed', 1)
                ->orderByDesc('start_date');
        });
    }
}
