<?php

namespace App\Models;

use App\Observers\ActivityObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
        'latitude',
        'longitude',
        'checkin_photo_path',
        'checkout_photo_path',
        'checkout_latitude',
        'checkout_longitude',
        'dosen_user_id',
        'week_group_id',
        'is_generated',
        'is_allowed',
        'created_via',
        'device_info',
        'is_overdue_checkout',
    ];

    protected $casts = [
        'device_info' => 'array',
        'is_overdue_checkout' => 'boolean',
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

    /**
     * Activity hasil check-in API yang belum checkout: tidak generated, `time_spend` nol, mulai = selesai.
     */
    public function isOpenCheckInWithoutCheckout(): bool
    {
        if ((int) $this->is_generated !== 0) {
            return false;
        }

        if ($this->time_spend !== '00:00:00') {
            return false;
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        return $start->equalTo($end);
    }

    /**
     * Terbuka seperti check-in dan sudah melewati ambang jam sejak `start_date` (default 24, sama logika check-in API).
     */
    public function isOverdueCheckoutByElapsedHours(int $hoursThreshold = 24): bool
    {
        if (! $this->isOpenCheckInWithoutCheckout()) {
            return false;
        }

        return Carbon::parse($this->start_date)->diffInHours(now()) > $hoursThreshold;
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
