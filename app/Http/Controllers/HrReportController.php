<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Models\Position;
use App\Models\ReviewPeriod;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeHr($request);
        $filters = $this->filters($request);

        return view('reports.hr', [
            'filters' => $filters,
            'rows' => $this->rows($filters),
            'periods' => ReviewPeriod::orderByDesc('starts_at')->get(),
            'units' => Unit::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeHr($request);
        $filters = $this->filters($request);
        $rows = $this->rows($filters);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Nomor Pegawai', 'Nama', 'Unit', 'Jabatan', 'Absensi', 'Absensi Valid',
                'Skor Merit', 'Pelatihan', 'Pelatihan Selesai', 'Mentoring', 'Mentoring Selesai',
            ]);
            foreach ($rows as $row) {
                fputcsv($output, array_map($this->csvValue(...), array_values($row)));
            }
            fclose($output);
        }, 'laporan-sdm-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param array<string, int|null> $filters */
    private function rows(array $filters): Collection
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

    /** @return array{review_period_id: int|null, unit_id: int|null, position_id: int|null} */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'review_period_id' => ['nullable', 'integer', 'exists:review_periods,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
        ]);

        return [
            'review_period_id' => isset($data['review_period_id']) ? (int) $data['review_period_id'] : null,
            'unit_id' => isset($data['unit_id']) ? (int) $data['unit_id'] : null,
            'position_id' => isset($data['position_id']) ? (int) $data['position_id'] : null,
        ];
    }

    private function authorizeHr(Request $request): void
    {
        abort_unless($request->user()?->is_active && $request->user()->role === UserRole::Hr, 403);
    }

    private function csvValue(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }
}
