<?php

namespace App\Services\Activity;

use App\DTOs\Activity\CreateActivityData;
use App\Models\Activity;
use App\Models\StaseLocation;
use App\Models\UnitStase;
use App\Models\WeekMonitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateActivityService
{
    public function execute(CreateActivityData $data): Activity
    {
        $startDate = Carbon::parse($data->date.' '.$data->startTime); // Tanggal asli tetap digunakan
        $startOfMonthDate = $startDate->copy()->startOfMonth(); // Variabel baru untuk awal bulan

        $now = Carbon::now();
        $nowStartOfMonth = $now->copy()->startOfMonth(); // Variabel baru untuk awal bulan saat ini

        // Hitung selisih bulan dari awal bulan ini
        $diffForward = $nowStartOfMonth->diffInMonths($startOfMonthDate, false); // `false` agar bisa negatif

        // Validasi jika lebih dari 2 bulan ke depan atau lebih dari 1 bulan ke belakang
        // if ($diffForward > 1) {
        //     throw new \Exception('Cannot insert activity more than 1 months ahead');
        // } elseif ($diffForward < -1) {
        //     throw new \Exception('Cannot insert activity more than 1 month in the past');
        // }

        $createdActivity = null;

        DB::transaction(function () use ($data, $startDate, &$createdActivity) {
            $endDate = Carbon::parse($data->date.' '.$data->finishTime);
            // Menghitung time_spend sebagai selisih antara end_date dan start_date
            $timeSpendInSeconds = $startDate->diffInSeconds($endDate);

            // Menghitung jam, menit, dan detik
            $hours = floor($timeSpendInSeconds / 3600);
            $minutes = floor(($timeSpendInSeconds % 3600) / 60);
            $seconds = $timeSpendInSeconds % 60;

            // Format dengan sprintf untuk memastikan dua digit
            $timeSpend = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            $date = Carbon::parse($startDate); // membuat instance Carbon dari start_date untuk me-looping satu minggu

            // Membuat week_group_id dengan format Tahun + Minggu (ISO-8601)
            $year = $date->year;
            $month = $date->month;
            $yearIso = $date->isoWeekYear;
            $weekIso = $date->isoWeek;
            $weekGroupId = intval($yearIso.$weekIso);

            $isWorkloadExceeded = false;

            $weekMonitor = WeekMonitor::where('user_id', $data->userId)
                ->where('week_group_id', $weekGroupId)
                ->first();
            if (empty($weekMonitor)) {
                $firstDayOfMonth = Carbon::create($year, $month, 1);
                $weekMonth = $date->diffInWeeks($firstDayOfMonth) + 1;

                $weekMonitor = new WeekMonitor;
                $weekMonitor->user_id = $data->userId;
                $weekMonitor->week_group_id = $weekGroupId;
                $weekMonitor->year = $year;
                $weekMonitor->week = $weekIso;
                $weekMonitor->month = $month;
                $weekMonitor->week_month = $weekMonth;
                $weekMonitor->workload_hours = 0;
                $weekMonitor->workload = 0;
                $weekMonitor->workload_as_seconds = 0;
                $weekMonitor->save();
            } else {
                $activity = Activity::where('user_id', $data->userId)
                    ->where('week_group_id', $weekGroupId)
                    ->where('is_allowed', 0)
                    ->first();
                if (! empty($activity)) {
                    throw new \Exception('Workload exceeded');
                }
                $next_workhours = $weekMonitor->workload_hours + $hours;
                if ($next_workhours > 88) {
                    throw new \Exception('Workload more than 88 hours');
                }
                if ($next_workhours > 80) {
                    $isWorkloadExceeded = true;
                }
            }

            $activity = Activity::where('user_id', $data->userId)
                ->where('week_group_id', $weekGroupId)
                ->first();

            if ($activity == null) {
                $date->startOfWeek(Carbon::MONDAY);

                for ($i = 0; $i < 7; $i++) {
                    Activity::create([
                        'user_id' => $data->userId,
                        'name' => '',
                        'type' => null,
                        'start_date' => $date->format('Y-m-d'),
                        'end_date' => $date->format('Y-m-d'),
                        'time_spend' => '00:00',
                        'description' => '',
                        'is_approved' => 0,
                        'approved_by' => null,
                        'approved_at' => null,
                        'unit_stase_id' => null,
                        'stase_id' => null,
                        'stase_location_id' => null,
                        'location_id' => null,
                        'dosen_user_id' => null,
                        'week_group_id' => $weekGroupId,
                        'is_generated' => 1,
                        'created_via' => 'system',
                        'device_info' => null,
                    ]);

                    $date->addDay();
                }
            }

            $unit_stase_id = null;
            if (! is_null($data->staseId)) {
                $unit_stase = UnitStase::where('stase_id', $data->staseId)
                    ->where('unit_id', $data->studentUnitId)
                    ->first();
                if (is_null($unit_stase)) {
                    throw new \Exception('Stase tidak terdaftar pada unit user.');
                }
                $unit_stase_id = $unit_stase->id;
            }

            $stase_location_id = null;
            if (! is_null($data->staseId)) {
                $stase_location = StaseLocation::where('stase_id', $data->staseId)
                    ->where('location_id', $data->locationId)
                    ->first();
                if (is_null($stase_location)) {
                    throw new \Exception('Lokasi tidak valid untuk stase yang dipilih.');
                }
                $stase_location_id = $stase_location->id;
            }

            $createdActivity = Activity::create([
                'user_id' => $data->userId,
                'name' => $data->name,
                'type' => $data->type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'time_spend' => $timeSpend,
                'description' => $data->description,
                'is_approved' => 0,
                'approved_by' => null,
                'approved_at' => null,
                'unit_stase_id' => $data->type == 'jaga' ? null : $unit_stase_id,
                'stase_id' => $data->type == 'jaga' ? null : $data->staseId,
                'stase_location_id' => $data->type == 'jaga' ? null : $stase_location_id,
                'location_id' => $data->type == 'jaga' ? null : $data->locationId,
                'dosen_user_id' => $data->dosenUserId,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
                'checkin_photo_path' => $data->checkinPhotoPath,
                'week_group_id' => $weekGroupId,
                'is_generated' => 0,
                'is_allowed' => $isWorkloadExceeded ? 0 : 1,
                'created_via' => $data->createdVia,
                'device_info' => $data->deviceInfo,
            ]);
        });

        if (! $createdActivity) {
            throw new \RuntimeException('Failed to create activity');
        }

        return $createdActivity;
    }
}
