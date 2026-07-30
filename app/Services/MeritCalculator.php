<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\ActivityLog;
use App\Models\DutyTrip;
use App\Models\EmployeeKpi;
use App\Models\MeritResult;
use App\Models\ReviewPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MeritCalculator
{
    public function publish(ReviewPeriod $period, User $hr): int
    {
        return DB::transaction(function () use ($period, $hr): int {
            $period = ReviewPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if ($hr->role !== UserRole::Hr || $period->published_at) {
                throw new BusinessRuleException('Periode tidak dapat dipublikasikan pengguna ini.');
            }

            $employees = User::where('role', UserRole::Employee)->where('is_active', true)->get();
            $periodStart = $period->starts_at->startOfDay();
            $periodEnd = $period->ends_at->endOfDay();
            $completedUntil = $periodEnd->isFuture() ? now() : $periodEnd;

            foreach ($employees as $employee) {
                $kpis = EmployeeKpi::where('review_period_id', $period->id)
                    ->where('employee_id', $employee->id)
                    ->get();
                $kpiScore = $kpis->isEmpty()
                    ? 0
                    : $kpis->avg(fn (EmployeeKpi $kpi): float => min(
                        (float) $kpi->achievement / (float) $kpi->target,
                        1.2,
                    ) * 100);

                $trips = DutyTrip::where('employee_id', $employee->id)
                    ->where('status', DutyTripStatus::Active)
                    ->whereBetween('ends_at', [$periodStart, $completedUntil]);
                $tripCount = (clone $trips)->count();
                $validCount = (clone $trips)->whereHas(
                    'attendance',
                    fn ($query) => $query->where('status', AttendanceStatus::Valid),
                )->count();
                $attendanceScore = $tripCount ? $validCount / $tripCount * 100 : 0;

                MeritResult::updateOrCreate(
                    ['review_period_id' => $period->id, 'employee_id' => $employee->id],
                    [
                        'kpi_score' => round($kpiScore, 2),
                        'attendance_score' => round($attendanceScore, 2),
                        'total_score' => round($kpiScore * 0.8 + $attendanceScore * 0.2, 2),
                    ],
                );
            }

            $period->forceFill(['published_at' => now()])->save();
            ActivityLog::record('merit.published', $period, $hr, ['employees' => $employees->count()]);

            return $employees->count();
        }, 3);
    }
}
