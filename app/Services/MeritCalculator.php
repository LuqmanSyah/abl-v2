<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ReviewType;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\EmployeeKpi;
use App\Models\MeritResult;
use App\Models\PerformanceReview;
use App\Models\ReviewPeriod;
use App\Models\User;

class MeritCalculator
{
    public function calculate(ReviewPeriod $period, User $employee): MeritResult
    {
        $kpis = EmployeeKpi::with('indicator')->where('review_period_id', $period->id)->where('employee_id', $employee->id)->get();
        $indicatorWeight = $kpis->sum(fn (EmployeeKpi $kpi) => $kpi->indicator->weight);
        $kpiScore = $indicatorWeight
            ? $kpis->sum(fn (EmployeeKpi $kpi) => min((float) $kpi->achievement / max((float) $kpi->target, 0.01), 1.2) * $kpi->indicator->weight) / $indicatorWeight * 100
            : 0;

        $attendances = Attendance::where('employee_id', $employee->id)->whereBetween('captured_at', [$period->starts_at->startOfDay(), $period->ends_at->endOfDay()]);
        $attendanceCount = (clone $attendances)->count();
        $disciplineScore = $attendanceCount ? (clone $attendances)->where('status', AttendanceStatus::Valid)->count() / $attendanceCount * 100 : 100;

        $managerScore = $this->reviewScore($period, $employee, [ReviewType::ManagerToEmployee]);
        $review360Score = $this->reviewScore($period, $employee, [ReviewType::EmployeeToManager, ReviewType::Peer]);
        $total = ($kpiScore * $period->kpi_weight + $disciplineScore * $period->discipline_weight
            + $managerScore * $period->manager_weight + $review360Score * $period->review_360_weight) / 100;

        $result = MeritResult::updateOrCreate(
            ['review_period_id' => $period->id, 'employee_id' => $employee->id],
            [
                'kpi_score' => round($kpiScore, 2), 'discipline_score' => round($disciplineScore, 2),
                'manager_score' => round($managerScore, 2), 'review_360_score' => round($review360Score, 2),
                'total_score' => round($total, 2), 'estimated_bonus' => round((float) $period->base_bonus * $total / 100, 2),
                'manager_verified_by' => null, 'manager_verified_at' => null, 'hr_verified_by' => null,
                'hr_verified_at' => null, 'published_at' => null,
            ],
        );

        ActivityLog::record('merit.calculated', $result);

        return $result;
    }

    /** @param array<ReviewType> $types */
    private function reviewScore(ReviewPeriod $period, User $employee, array $types): float
    {
        $average = PerformanceReview::where('review_period_id', $period->id)
            ->where('reviewee_id', $employee->id)->whereIn('type', array_map(fn (ReviewType $type) => $type->value, $types))->avg('score');

        return $average ? (float) $average / 5 * 100 : 0;
    }
}
