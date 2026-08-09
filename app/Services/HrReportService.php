<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Models\ReviewPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HrReportService
{
    /** @param array<string, mixed> $filters */
    public function rows(array $filters): Collection
    {
        $period = $filters['review_period_id'] ? ReviewPeriod::find($filters['review_period_id']) : null;
        $attendanceScope = fn (Builder $query) => $query->when(
            $period,
            fn (Builder $query) => $query->whereBetween('captured_at', [$period->starts_at->startOfDay(), $period->ends_at->endOfDay()]),
        );
        $trainingScope = fn (Builder $query) => $query->when(
            $period,
            fn (Builder $query) => $query->whereBetween('requested_at', [$period->starts_at->startOfDay(), $period->ends_at->endOfDay()]),
        );
        $mentoringScope = fn (Builder $query) => $query->when(
            $period,
            fn (Builder $query) => $query->whereBetween('requested_at', [$period->starts_at->startOfDay(), $period->ends_at->endOfDay()]),
        );

        return User::query()
            ->where('role', UserRole::Employee)
            ->when($filters['unit_id'], fn (Builder $query, int $id) => $query->where('unit_id', $id))
            ->when($filters['position_id'], fn (Builder $query, int $id) => $query->where('position_id', $id))
            ->with([
                'unit',
                'position',
                'meritResults' => fn ($query) => $query
                    ->when($period, fn ($query) => $query->where('review_period_id', $period->id))
                    ->latest('created_at')
                    ->limit(1),
            ])
            ->withCount([
                'attendances as attendance_count' => $attendanceScope,
                'attendances as valid_attendance_count' => fn (Builder $query) => $attendanceScope($query)->where('status', AttendanceStatus::Valid),
                'trainingRequests as training_count' => $trainingScope,
                'trainingRequests as completed_training_count' => fn (Builder $query) => $trainingScope($query)->where('status', TrainingRequestStatus::Completed),
                'mentorings as mentoring_count' => $mentoringScope,
                'mentorings as completed_mentoring_count' => fn (Builder $query) => $mentoringScope($query)->where('status', MentoringStatus::Completed),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (User $employee): array => [
                'employee_number' => $employee->employee_number ?? '-',
                'name' => $employee->name,
                'unit' => $employee->unit?->name ?? '-',
                'position' => $employee->position?->name ?? '-',
                'attendance_count' => $employee->attendance_count,
                'valid_attendance_count' => $employee->valid_attendance_count,
                'merit_score' => $employee->meritResults->first()?->total_score ?? '-',
                'training_count' => $employee->training_count,
                'completed_training_count' => $employee->completed_training_count,
                'mentoring_count' => $employee->mentoring_count,
                'completed_mentoring_count' => $employee->completed_mentoring_count,
            ]);
    }
}
