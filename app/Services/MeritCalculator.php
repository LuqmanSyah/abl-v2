<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\ReviewType;
use App\Exceptions\BusinessRuleException;
use App\Models\ActivityLog;
use App\Models\DutyTrip;
use App\Models\EmployeeKpi;
use App\Models\MeritResult;
use App\Models\PerformanceReview;
use App\Models\ReviewPeriod;
use App\Models\User;
use App\Notifications\MeritReadyForVerification;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class MeritCalculator
{
    public function calculate(ReviewPeriod $period, User $employee): MeritResult
    {
        return DB::transaction(function () use ($period, $employee): MeritResult {
            $period = ReviewPeriod::query()->lockForUpdate()->findOrFail($period->id);
            $identity = ['review_period_id' => $period->id, 'employee_id' => $employee->id];
            $result = MeritResult::where($identity)->lockForUpdate()->first();

            if (! $period->hasStarted()) {
                throw new BusinessRuleException('Hasil merit belum dapat dihitung sebelum periode dimulai.');
            }
            if (! $period->is_active) {
                throw new BusinessRuleException('Hasil merit hanya dapat dihitung untuk periode aktif.');
            }
            if ($result?->manager_verified_at || $result?->hr_verified_at || $result?->published_at) {
                throw new DomainException('Hasil merit sudah diverifikasi dan tidak dapat dihitung ulang.');
            }
            if ($period->hasPublishedMeritResults()) {
                throw new BusinessRuleException('Hasil merit yang telah dipublikasikan tidak dapat dihitung ulang.');
            }

            $kpis = EmployeeKpi::with('indicator')->where('review_period_id', $period->id)->where('employee_id', $employee->id)->get();
            $missing = collect();

            if ($period->kpi_weight > 0) {
                $indicators = $period->indicators()->get();
                $totalIndicatorWeight = (int) $indicators->sum('weight');

                if ($totalIndicatorWeight !== 100) {
                    throw new BusinessRuleException("Total bobot indikator KPI wajib 100% (saat ini {$totalIndicatorWeight}%).");
                }

                if ($indicators->pluck('id')->diff($kpis->pluck('kpi_indicator_id'))->isNotEmpty()) {
                    $missing->push('KPI Pegawai');
                }
            }

            if ($period->manager_weight > 0 && PerformanceReview::where('review_period_id', $period->id)
                ->where('reviewee_id', $employee->id)
                ->where('type', ReviewType::ManagerToEmployee)
                ->doesntExist()) {
                $missing->push('penilaian Atasan');
            }

            if ($period->review_360_weight > 0 && PerformanceReview::where('review_period_id', $period->id)
                ->where('reviewee_id', $employee->id)
                ->where('type', ReviewType::Peer)
                ->doesntExist()) {
                $missing->push('umpan balik Rekan');
            }

            if ($missing->isNotEmpty()) {
                throw new BusinessRuleException('Data merit belum lengkap: '.$missing->join(', ').'.');
            }

            $indicatorWeight = $kpis->sum(fn (EmployeeKpi $kpi) => $kpi->indicator?->weight ?? 0);
            $kpiScore = $indicatorWeight
                ? $kpis->sum(fn (EmployeeKpi $kpi) => min((float) $kpi->achievement / max((float) $kpi->target, 0.01), 1.2) * ($kpi->indicator?->weight ?? 0)) / $indicatorWeight * 100
                : 0;

            $periodStart = $period->starts_at->startOfDay();
            $periodEnd = $period->ends_at->endOfDay();
            $dutyTrips = DutyTrip::where('employee_id', $employee->id)
                ->where('starts_at', '<=', $periodEnd)
                ->where('ends_at', '>=', $periodStart)
                ->where('status', DutyTripStatus::Approved)
                ->where('ends_at', '<=', now())
                ->with(['attendances' => fn ($q) => $q
                    ->where('status', AttendanceStatus::Valid)
                    ->whereBetween('captured_at', [$periodStart, $periodEnd]),
                ])->get();
            $allDates = collect();
            $validDates = collect();
            foreach ($dutyTrips as $trip) {
                $tripStart = CarbonImmutable::parse($trip->starts_at)->max($periodStart);
                $tripEnd = CarbonImmutable::parse($trip->ends_at)->min($periodEnd);
                $range = collect($tripStart->toPeriod($tripEnd))
                    ->map(fn ($d) => $d->toDateString());
                $allDates = $allDates->merge($range)->unique();
                $validDates = $validDates->merge(
                    $trip->attendances->pluck('attendance_date')->map(fn ($d) => $d instanceof CarbonImmutable ? $d->toDateString() : $d)
                )->unique();
            }
            $totalDays = $allDates->count();
            $validDays = $validDates->count();
            $disciplineScore = $totalDays ? min($validDays / $totalDays * 100, 100) : 100;

            $managerScore = $this->reviewScore($period, $employee, [ReviewType::ManagerToEmployee]);
            $review360Score = $this->reviewScore($period, $employee, [ReviewType::Peer]);
            $total = ($kpiScore * $period->kpi_weight + $disciplineScore * $period->discipline_weight
                + $managerScore * $period->manager_weight + $review360Score * $period->review_360_weight) / 100;
            $scores = [
                'kpi_score' => round($kpiScore, 2), 'discipline_score' => round($disciplineScore, 2),
                'manager_score' => round($managerScore, 2), 'review_360_score' => round($review360Score, 2),
                'total_score' => round($total, 2), 'estimated_bonus' => round((float) $period->base_bonus * $total / 100, 2),
                'calculated_at' => now(),
                'manager_verified_by' => null, 'manager_verified_at' => null, 'hr_verified_by' => null,
                'hr_verified_at' => null, 'published_at' => null,
            ];

            if ($result) {
                $result->update($scores);
            } else {
                $result = MeritResult::create([...$identity, ...$scores]);
            }

            ActivityLog::record('merit.calculated', $result);

            if (! $result->wasRecentlyCreated && $result->wasChanged('total_score')) {
                ActivityLog::record('merit.recalculated', $result);
            }

            if ($result->wasRecentlyCreated) {
                $employee->manager?->notify(new MeritReadyForVerification($result));
            }

            return $result;
        }, 3);
    }

    /** @param array<ReviewType> $types */
    private function reviewScore(ReviewPeriod $period, User $employee, array $types): float
    {
        $average = PerformanceReview::where('review_period_id', $period->id)
            ->where('reviewee_id', $employee->id)->whereIn('type', array_map(fn (ReviewType $type) => $type->value, $types))->avg('score');

        return $average !== null ? (float) $average / 5 * 100 : 0;
    }
}
