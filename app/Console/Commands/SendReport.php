<?php

namespace App\Console\Commands;

use App\Mail\ReportMail;
use App\Models\ReviewPeriod;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendReport extends Command
{
    protected $signature = 'merit:send-report {--review_period_id=} {--unit_id=} {--position_id=}';
    protected $description = 'Kirim laporan SDM periodik ke HR via email';

    public function handle(): int
    {
        $periodId = $this->option('review_period_id');
        $unitId = $this->option('unit_id');
        $positionId = $this->option('position_id');

        $period = $periodId ? ReviewPeriod::find($periodId) : ReviewPeriod::where('is_active', true)->latest('ends_at')->first();
        $filters = [
            'review_period_id' => $period?->id,
            'unit_id' => $unitId ? (int) $unitId : null,
            'position_id' => $positionId ? (int) $positionId : null,
        ];

        $rows = $this->reportRows($filters);

        if ($rows->isEmpty()) {
            $this->warn('Tidak ada data laporan.');

            return 0;
        }

        $periods = ReviewPeriod::orderByDesc('starts_at')->get();
        $dateLabel = now()->format('Y-m-d');

        $hrUsers = User::where('role', UserRole::Hr)->where('is_active', true)->get();

        if ($hrUsers->isEmpty()) {
            $this->warn('Tidak ada pengguna HR aktif.');

            return 0;
        }

        foreach ($hrUsers as $hr) {
            Mail::to($hr->email)->send(new ReportMail($rows, $filters, $periods, $dateLabel));
        }

        $this->info("Laporan dikirim ke {$hrUsers->count()} HR.");

        return 0;
    }

    /** @param array<string, int|null> $filters */
    private function reportRows(array $filters): \Illuminate\Support\Collection
    {
        $period = $filters['review_period_id'] ? ReviewPeriod::find($filters['review_period_id']) : null;
        $attendanceScope = fn ($query) => $query->when(
            $period,
            fn ($query) => $query->whereBetween('captured_at', [$period->starts_at->startOfDay(), $period->ends_at->endOfDay()]),
        );
        $trainingScope = fn ($query) => $query->when(
            $period,
            fn ($query) => $query->whereBetween('requested_at', [$period->starts_at->startOfDay(), $period->ends_at->endOfDay()]),
        );
        $mentoringScope = fn ($query) => $query->when(
            $period,
            fn ($query) => $query->whereBetween('requested_at', [$period->starts_at->startOfDay(), $period->ends_at->endOfDay()]),
        );

        return User::query()
            ->where('role', UserRole::Employee)
            ->when($unitId = $filters['unit_id'], fn ($query, int $id) => $query->where('unit_id', $id))
            ->when($positionId = $filters['position_id'], fn ($query, int $id) => $query->where('position_id', $id))
            ->with([
                'unit', 'position',
                'meritResults' => fn ($query) => $query
                    ->when($period, fn ($query) => $query->where('review_period_id', $period->id))
                    ->latest('created_at')->limit(1),
            ])
            ->withCount([
                'attendances as attendance_count' => $attendanceScope,
                'attendances as valid_attendance_count' => fn ($query) => $attendanceScope($query)->where('status', \App\Enums\AttendanceStatus::Valid),
                'trainingRequests as training_count' => $trainingScope,
                'trainingRequests as completed_training_count' => fn ($query) => $trainingScope($query)->where('status', \App\Enums\TrainingRequestStatus::Completed),
                'mentorings as mentoring_count' => $mentoringScope,
                'mentorings as completed_mentoring_count' => fn ($query) => $mentoringScope($query)->where('status', \App\Enums\MentoringStatus::Completed),
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
