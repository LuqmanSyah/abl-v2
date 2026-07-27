<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\PerformanceReview;
use App\Models\ReviewKpiDetail;

class MeritScoreService
{
    public function __construct(private AttendanceScoreService $attendanceScore) {}

    public function calculate(PerformanceReview $review, bool $force = false): PerformanceReview
    {
        if ($review->status === ReviewStatus::Locked && ! $force) {
            throw new BusinessRuleException('Rapor terkunci hanya dapat dihitung ulang secara paksa.');
        }

        if (! in_array($review->status, [ReviewStatus::Submitted, ReviewStatus::Approved], true)
            && ! ($review->status === ReviewStatus::Locked && $force)) {
            throw new BusinessRuleException('Merit hanya dapat dihitung untuk rapor yang sudah disubmit.');
        }

        $managerKpiScore = round((float) $review->reviewKpiDetails()
            ->get(['manager_score', 'weight'])
            ->sum(fn (ReviewKpiDetail $detail): float => (float) $detail->manager_score * (float) $detail->weight / 100), 2);
        $attendanceScore = $this->attendanceScore->calculate(
            $review->user_id,
            $review->start_date,
            $review->end_date,
        );
        $finalMeritScore = round(0.2 * $attendanceScore + 0.8 * $managerKpiScore, 2);

        $review->forceFill([
            'attendance_score' => $attendanceScore,
            'manager_kpi_score' => $managerKpiScore,
            'final_merit_score' => $finalMeritScore,
            'grade' => match (true) {
                $finalMeritScore >= 85 => 'A',
                $finalMeritScore >= 70 => 'B',
                $finalMeritScore >= 55 => 'C',
                default => 'D',
            },
        ])->saveQuietly();

        return $review->refresh();
    }
}
