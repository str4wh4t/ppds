<?php

namespace App\Services\Activity;

use App\Models\Unit;
use App\Models\User;
use App\Support\StatisticPeriodHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildActivityStatisticService
{
    /**
     * @param  Collection<int, int>|null  $adminUnitScope
     * @return array{
     *     barData: Collection,
     *     pieData: Collection,
     *     tableData: Collection,
     *     weekMonitorStats: Collection,
     *     pieChartData: array<int, array{name: string, value: mixed}>
     * }
     */
    public function execute(
        ?string $yearSelected,
        ?string $monthIndexSelected,
        ?string $weekIndexSelected,
        ?Collection $adminUnitScope
    ): array {
        if ($adminUnitScope !== null && $adminUnitScope->isEmpty()) {
            return $this->emptyPayload();
        }

        $periodEnd = StatisticPeriodHelper::fromRequest($yearSelected, $monthIndexSelected, $weekIndexSelected)['periodEnd'];

        $totalUsersQuery = User::withoutGlobalScopes()
            ->select('student_unit_id', DB::raw('COUNT(id) as total_users'))
            ->where('is_active_student', 1)
            ->when($adminUnitScope, fn ($q) => $q->whereIn('student_unit_id', $adminUnitScope));

        StatisticPeriodHelper::applyRegisteredBefore($totalUsersQuery, $periodEnd, 'created_at');

        $totalUsersPerUnit = $totalUsersQuery
            ->groupBy('student_unit_id')
            ->pluck('total_users', 'student_unit_id');

        $monitoredUsersPerUnit = Unit::select(
            'units.id',
            'units.name as name',
            DB::raw('COALESCE(COUNT(DISTINCT week_monitors.user_id), 0) as monitored_users')
        )
            ->when($adminUnitScope, fn ($q) => $q->whereIn('units.id', $adminUnitScope))
            ->leftJoin('users', function ($join) use ($periodEnd) {
                StatisticPeriodHelper::applyActiveStudentJoin($join, $periodEnd);
            })
            ->leftJoin('week_monitors', function ($join) use ($yearSelected, $monthIndexSelected, $weekIndexSelected) {
                $join->on('week_monitors.user_id', '=', 'users.id');

                if ($yearSelected) {
                    $join->where('week_monitors.year', $yearSelected);
                }
                if ($monthIndexSelected) {
                    $join->where('week_monitors.month', $monthIndexSelected);
                }
                if ($weekIndexSelected) {
                    $join->where('week_monitors.week_month', $weekIndexSelected);
                }
            })
            ->groupBy('units.id')
            ->get();

        $barData = $monitoredUsersPerUnit->map(function ($unit) use ($totalUsersPerUnit) {
            $totalUsers = $totalUsersPerUnit[$unit->id] ?? 1;

            return [
                'name' => $unit->name,
                'value' => round(($unit->monitored_users / $totalUsers) * 100, 2),
            ];
        });

        $tableData = $monitoredUsersPerUnit->map(function ($unit) use ($totalUsersPerUnit) {
            $totalUsers = $totalUsersPerUnit[$unit->id] ?? 0;
            $monitoredUsers = $unit->monitored_users;
            $notMonitoredUsers = $totalUsers - $monitoredUsers;
            $percentage = $totalUsers > 0 ? round(($monitoredUsers / $totalUsers) * 100, 2) : 0;

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'total_users' => $totalUsers,
                'monitored_users' => $monitoredUsers,
                'not_monitored_users' => max($notMonitoredUsers, 0),
                'percentage' => $percentage,
            ];
        });

        $weekMonitorStats = Unit::select(
            'units.id as id',
            'units.name as name',
            DB::raw('COUNT(DISTINCT week_monitors.user_id) as total_monitored_users'),
            DB::raw('SUM(CASE WHEN week_monitors.workload_hours < 71 THEN 1 ELSE 0 END) as workload_below_71'),
            DB::raw('SUM(CASE WHEN week_monitors.workload_hours BETWEEN 71 AND 80 THEN 1 ELSE 0 END) as workload_71_to_80'),
            DB::raw('SUM(CASE WHEN week_monitors.workload_hours > 80 THEN 1 ELSE 0 END) as workload_above_80')
        )
            ->when($adminUnitScope, fn ($q) => $q->whereIn('units.id', $adminUnitScope))
            ->leftJoin('users', function ($join) use ($periodEnd) {
                StatisticPeriodHelper::applyActiveStudentJoin($join, $periodEnd);
            })
            ->leftJoin('week_monitors', function ($join) use ($yearSelected, $monthIndexSelected, $weekIndexSelected) {
                $join->on('week_monitors.user_id', '=', 'users.id');

                if ($yearSelected) {
                    $join->where('week_monitors.year', $yearSelected);
                }
                if ($monthIndexSelected) {
                    $join->where('week_monitors.month', $monthIndexSelected);
                }
                if ($weekIndexSelected) {
                    $join->where('week_monitors.week_month', $weekIndexSelected);
                }
            })
            ->groupBy('units.id')
            ->get();

        $workloadPieRecord = Unit::query()
            ->when($adminUnitScope, fn ($q) => $q->whereIn('units.id', $adminUnitScope))
            ->leftJoin('users', function ($join) use ($periodEnd) {
                StatisticPeriodHelper::applyActiveStudentJoin($join, $periodEnd);
            })
            ->leftJoin('week_monitors', function ($join) use ($yearSelected, $monthIndexSelected, $weekIndexSelected) {
                $join->on('week_monitors.user_id', '=', 'users.id');

                if ($yearSelected) {
                    $join->where('week_monitors.year', $yearSelected);
                }
                if ($monthIndexSelected) {
                    $join->where('week_monitors.month', $monthIndexSelected);
                }
                if ($weekIndexSelected) {
                    $join->where('week_monitors.week_month', $weekIndexSelected);
                }
            })
            ->select(
                DB::raw('COALESCE(SUM(CASE WHEN week_monitors.workload_hours < 71 THEN 1 ELSE 0 END), 0) as workload_below_71'),
                DB::raw('COALESCE(SUM(CASE WHEN week_monitors.workload_hours BETWEEN 71 AND 80 THEN 1 ELSE 0 END), 0) as workload_71_to_80'),
                DB::raw('COALESCE(SUM(CASE WHEN week_monitors.workload_hours > 80 THEN 1 ELSE 0 END), 0) as workload_above_80')
            )
            ->first();

        $pieChartData = [
            ['name' => 'Workload < 71', 'value' => $workloadPieRecord->workload_below_71],
            ['name' => 'Workload 71 - 80', 'value' => $workloadPieRecord->workload_71_to_80],
            ['name' => 'Workload > 80', 'value' => $workloadPieRecord->workload_above_80],
        ];

        $pieData = Unit::select(
            'units.name as name',
            DB::raw('COUNT(DISTINCT week_monitors.user_id) as value')
        )
            ->when($adminUnitScope, fn ($q) => $q->whereIn('units.id', $adminUnitScope))
            ->leftJoin('users', function ($join) use ($periodEnd) {
                StatisticPeriodHelper::applyActiveStudentJoin($join, $periodEnd);
            })
            ->leftJoin('week_monitors', function ($join) use ($yearSelected, $monthIndexSelected, $weekIndexSelected) {
                $join->on('week_monitors.user_id', '=', 'users.id');

                if ($yearSelected) {
                    $join->where('week_monitors.year', $yearSelected);
                }
                if ($monthIndexSelected) {
                    $join->where('week_monitors.month', $monthIndexSelected);
                }
                if ($weekIndexSelected) {
                    $join->where('week_monitors.week_month', $weekIndexSelected);
                }
            })
            ->groupBy('units.id')
            ->orderByDesc('value')
            ->get();

        return [
            'barData' => $barData,
            'pieData' => $pieData,
            'tableData' => $tableData,
            'weekMonitorStats' => $weekMonitorStats,
            'pieChartData' => $pieChartData,
        ];
    }

    /**
     * @return array{
     *     barData: Collection,
     *     pieData: Collection,
     *     tableData: Collection,
     *     weekMonitorStats: Collection,
     *     pieChartData: array<int, array{name: string, value: int}>
     * }
     */
    private function emptyPayload(): array
    {
        return [
            'barData' => collect(),
            'pieData' => collect(),
            'tableData' => collect(),
            'weekMonitorStats' => collect(),
            'pieChartData' => [
                ['name' => 'Workload < 71', 'value' => 0],
                ['name' => 'Workload 71 - 80', 'value' => 0],
                ['name' => 'Workload > 80', 'value' => 0],
            ],
        ];
    }
}
