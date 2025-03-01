<?php

namespace App\Observers;

use App\Events\WorkloadExceeded;
use App\Models\Activity;
use App\Models\WeekMonitor;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ActivityObserver
{

    private function calculateWorkload(Activity $activity)
    {
        //
        $user = $activity->user;

        $weekGroupId = $activity->week_group_id;

        // Ambil semua aktivitas user dalam rentang minggu ini
        $activities = Activity::where(['user_id' => $user->id, 'week_group_id' => $weekGroupId])
            ->get();

        // Hitung total jam kerja (workload) untuk minggu ini
        // $totalWorkload = $activities->sum(function ($activity) {
        //     $start = Carbon::parse($activity->start_time);
        //     $finish = Carbon::parse($activity->finish_time);
        //     return $finish->diffInHours($start);
        // });
        $totalSeconds = $activities->sum(function ($activity) {
            // Mengonversi kolom `time_spend` ke detik
            list($hours, $minutes, $seconds) = explode(':', $activity->time_spend);
            return ($hours * 3600) + ($minutes * 60) + $seconds;
        });

        // Mengonversi total detik menjadi jam:menit
        // $totalWorkload = CarbonInterval::seconds($totalSeconds)->cascade()->format('%H:%I');

        $hours = floor($totalSeconds / 3600);       // Menghitung total jam
        $minutes = floor(($totalSeconds % 3600) / 60); // Menghitung sisa menit
        // $seconds = $totalSeconds % 60;                 // Sisa detik setelah jam dan menit

        // Mengembalikan dalam format jam:menit:detik
        // $totalWorkload = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        $totalWorkload = sprintf('%02d:%02d', $hours, $minutes);

        // Log atau simpan workload mingguan
        // $weekMonitor = WeekMonitor::updateOrCreate(
        //     ['user_id' => $user->id, 'week_group_id' => $weekGroupId],  // Kondisi pencarian
        //     ['year' => substr($weekGroupId, 0, 4), 'week' => substr($weekGroupId, 4, 2), 'workload_hours' => $hours, 'workload' => $totalWorkload, 'workload_as_seconds' => $totalSeconds]           // Data yang akan diupdate/insert
        // );

        $weekMonitor = WeekMonitor::where('user_id', $user->id)
            ->where('week_group_id', $weekGroupId)
            ->first();

        if (!empty($weekMonitor)) {

            if($hours == 0){            
                $weekMonitor->delete();
            }else{
                // $weekMonitor->user_id = $user->id;
                // $weekMonitor->week_group_id = $weekGroupId;
    
                // Mengambil tahun dan minggu dari weekGroupId
                // $year = substr($weekGroupId, 0, 4);
                // $week = substr($weekGroupId, 4, 2);
    
                // Menentukan tanggal pertama dari minggu tersebut
                // $date = Carbon::now()->setISODate($year, $week);
    
                // Menentukan bulan berdasarkan tanggal minggu tersebut
                // $month = $date->month;
    
                // Menentukan minggu keberapa dalam bulan tersebut
                // $firstDayOfMonth = Carbon::create($year, $month, 1);
                // $weekMonth = $date->diffInWeeks($firstDayOfMonth) + 1;
    
                // $weekMonitor->year = $year;
                // $weekMonitor->week = $week;
                // $weekMonitor->month = $month;
                // $weekMonitor->week_month = $weekMonth;
    
                $weekMonitor->workload_hours = $hours;
                $weekMonitor->workload = $totalWorkload;
                $weekMonitor->workload_as_seconds = $totalSeconds;
                $weekMonitor->save();
            }
        }

        if ($hours > 80) {
            event(new WorkloadExceeded($weekMonitor, $activity));
        }

        
    }

    /**
     * Handle the Activity "created" event.
     */
    public function created(Activity $activity): void
    {
        //
    }

    /**
     * Handle the Activity "saved" event.
     */
    public function saved(Activity $activity): void
    {
        //
        if (!$activity->is_generated) {
            $this->calculateWorkload($activity);
            // Log::info('Activity saved');
        }
    }

    /**
     * Handle the Activity "updated" event.
     */
    public function updated(Activity $activity): void
    {
        //
    }

    /**
     * Handle the Activity "deleted" event.
     */
    public function deleted(Activity $activity): void
    {
        //
        $this->calculateWorkload($activity);
    }

    /**
     * Handle the Activity "restored" event.
     */
    public function restored(Activity $activity): void
    {
        //
    }

    /**
     * Handle the Activity "force deleted" event.
     */
    public function forceDeleted(Activity $activity): void
    {
        //
    }
}
