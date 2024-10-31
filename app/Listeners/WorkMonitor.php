<?php

namespace App\Listeners;

use App\Events\ActivityLogged;
use App\Models\Activity;
use App\Models\WeekMonitor;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WorkMonitor
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ActivityLogged $event)
    {
        $user = $event->user;
        $activity = $event->activity;

        $weekGroupId = $activity['week_group_id'];

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
        WeekMonitor::updateOrCreate(
            ['user_id' => $user->id, 'week_group_id' => $weekGroupId],  // Kondisi pencarian
            ['workload' => $totalWorkload, 'workload_as_seconds' => $totalSeconds]           // Data yang akan diupdate/insert
        );
    }
}
