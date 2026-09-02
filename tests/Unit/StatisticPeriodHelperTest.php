<?php

namespace Tests\Unit;

use App\Support\StatisticPeriodHelper;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class StatisticPeriodHelperTest extends TestCase
{
    public function test_period_end_for_year_only(): void
    {
        $end = StatisticPeriodHelper::periodEnd(2026, null, null);

        $this->assertEquals('2026-12-31 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_period_end_for_year_and_month(): void
    {
        $end = StatisticPeriodHelper::periodEnd(2026, 5, null);

        $this->assertEquals('2026-05-31 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_period_end_for_week_within_month(): void
    {
        $end = StatisticPeriodHelper::periodEnd(2026, 5, 2);

        $this->assertEquals('2026-05-14 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_period_end_caps_at_end_of_month_for_late_weeks(): void
    {
        $end = StatisticPeriodHelper::periodEnd(2026, 2, 5);

        $this->assertEquals(Carbon::create(2026, 2, 1)->endOfMonth()->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'));
    }

    public function test_period_end_returns_null_without_year(): void
    {
        $this->assertNull(StatisticPeriodHelper::periodEnd(null, 5, 2));
    }
}
