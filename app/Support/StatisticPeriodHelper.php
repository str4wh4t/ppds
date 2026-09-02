<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class StatisticPeriodHelper
{
    /**
     * Akhir periode filter statistic (mahasiswa dihitung jika sudah terdaftar pada atau sebelum titik ini).
     *
     * @param  int|null  $year  Tahun kalender (contoh: 2026)
     * @param  int|null  $month  Bulan 1–12
     * @param  int|null  $weekMonth  Minggu ke-N dalam bulan (1–5), selaras dengan week_monitors.week_month
     */
    public static function periodEnd(?int $year, ?int $month, ?int $weekMonth): ?Carbon
    {
        if (! $year) {
            return null;
        }

        if ($month && $weekMonth) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
            $endOfWeek = $startOfMonth->copy()->addWeeks($weekMonth)->subSecond();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            return $endOfWeek->gt($endOfMonth) ? $endOfMonth : $endOfWeek;
        }

        if ($month) {
            return Carbon::create($year, $month, 1)->endOfMonth();
        }

        return Carbon::create($year, 12, 31)->endOfDay();
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function applyRegisteredBefore(Builder $query, ?Carbon $periodEnd, string $column = 'users.created_at'): Builder
    {
        if ($periodEnd === null) {
            return $query;
        }

        return $query->where($column, '<=', $periodEnd);
    }

    public static function applyActiveStudentJoin(JoinClause $join, ?Carbon $periodEnd): void
    {
        $join->on('users.student_unit_id', '=', 'units.id')
            ->where('users.is_active_student', 1);

        if ($periodEnd !== null) {
            $join->where('users.created_at', '<=', $periodEnd);
        }
    }

    /**
     * @return array{year: int|null, month: int|null, weekMonth: int|null, periodEnd: Carbon|null}
     */
    public static function fromRequest(?string $yearSelected, ?string $monthIndexSelected, ?string $weekIndexSelected): array
    {
        $year = $yearSelected !== null && $yearSelected !== '' ? (int) $yearSelected : null;
        $month = $monthIndexSelected !== null && $monthIndexSelected !== '' ? (int) $monthIndexSelected : null;
        $weekMonth = $weekIndexSelected !== null && $weekIndexSelected !== '' ? (int) $weekIndexSelected : null;

        return [
            'year' => $year,
            'month' => $month,
            'weekMonth' => $weekMonth,
            'periodEnd' => self::periodEnd($year, $month, $weekMonth),
        ];
    }
}
