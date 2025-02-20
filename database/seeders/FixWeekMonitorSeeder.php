<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixWeekMonitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $weekMonitors = DB::table('week_monitors')->get();

        foreach ($weekMonitors as $monitor) {
            $year = $monitor->year;
            $week = $monitor->week;

            // Menentukan tanggal pertama dari minggu tertentu
            $date = Carbon::now()->setISODate($year, $week);
            
            // Mendapatkan index bulan (1 - 12)
            $month = $date->month;
            
            // Menghitung indeks minggu dalam bulan
            $firstDayOfMonth = Carbon::create($year, $month, 1);
            $weekMonth = $date->diffInWeeks($firstDayOfMonth) + 1;

            // Update record
            DB::table('week_monitors')
                ->where('id', $monitor->id)
                ->update([
                    'month' => $month,
                    'week_month' => $weekMonth,
                ]);
        }
    }
}
