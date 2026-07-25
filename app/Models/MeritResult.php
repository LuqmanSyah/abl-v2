<?php

namespace App\Models;

use App\Enums\DutyTripStatus;
use App\Enums\ReviewType;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Notifications\MeritPublished;
use App\Notifications\MeritReadyForVerification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class MeritResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_period_id', 'employee_id', 'kpi_score', 'discipline_score', 'manager_score',
        'review_360_score', 'total_score', 'estimated_bonus', 'manager_verified_by',
        'calculated_at', 'manager_verified_at', 'hr_verified_by', 'hr_verified_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'kpi_score' => 'decimal:2', 'discipline_score' => 'decimal:2', 'manager_score' => 'decimal:2',
            'review_360_score' => 'decimal:2', 'total_score' => 'decimal:2', 'estimated_bonus' => 'decimal:2',
            'calculated_at' => 'datetime', 'manager_verified_at' => 'datetime',
            'hr_verified_at' => 'datetime', 'published_at' => 'datetime',
        ];
    }

    public function reviewPeriod(): BelongsTo
    {
        return $this->belongsTo(ReviewPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function managerVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_verified_by');
    }

    public function hrVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_verified_by');
    }

    /** @return array<string, mixed> */
    public function breakdownForManager(User $manager): array
    {
        if ($manager->role !== UserRole::Manager || $this->employee->manager_id !== $manager->id) {
            throw new BusinessRuleException('Rincian merit tidak dapat dilihat pengguna ini.');
        }

        $period = $this->reviewPeriod;
        $kpis = EmployeeKpi::with('indicator')
            ->where('review_period_id', $period->id)
            ->where('employee_id', $this->employee_id)
            ->get();
        $kpiLogs = ActivityLog::with('user')
            ->where('subject_type', (new EmployeeKpi)->getMorphClass())
            ->whereIn('subject_id', $kpis->modelKeys())
            ->whereIn('action', ['kpi.created', 'kpi.updated'])
            ->oldest()
            ->get()
            ->groupBy('subject_id');

        $reviews = PerformanceReview::with('reviewer')
            ->where('review_period_id', $period->id)
            ->where('reviewee_id', $this->employee_id)
            ->whereIn('type', [
                ReviewType::ManagerToEmployee->value,
                ReviewType::EmployeeToManager->value,
                ReviewType::Peer->value,
            ])
            ->get();

        $dutyTrips = DutyTrip::with('attendances')
            ->where('employee_id', $this->employee_id)
            ->where('starts_at', '<=', $period->ends_at->copy()->endOfDay())
            ->where('ends_at', '>=', $period->starts_at->copy()->startOfDay())
            ->where(function (Builder $query): void {
                $query->where('status', DutyTripStatus::Completed)
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', DutyTripStatus::Approved)
                        ->where('ends_at', '<=', now()));
            })
            ->get();

        return [
            'period' => $period->name,
            'scores' => [
                'kpi' => $this->kpi_score,
                'discipline' => $this->discipline_score,
                'manager' => $this->manager_score,
                'review_360' => $this->review_360_score,
                'total' => $this->total_score,
                'estimated_bonus' => $this->estimated_bonus,
            ],
            'weights' => [
                'kpi' => $period->kpi_weight,
                'discipline' => $period->discipline_weight,
                'manager' => $period->manager_weight,
                'review_360' => $period->review_360_weight,
            ],
            'kpis' => $kpis->map(function (EmployeeKpi $kpi) use ($kpiLogs): array {
                $ratio = min((float) $kpi->achievement / max((float) $kpi->target, 0.01), 1.2);

                return [
                    'indicator' => $kpi->indicator?->name ?? 'Indikator dihapus',
                    'target' => $kpi->target,
                    'achievement' => $kpi->achievement,
                    'score' => round($ratio * 100, 2),
                    'weight' => $kpi->indicator?->weight ?? 0,
                    'history' => $kpiLogs->get($kpi->id, collect())->map(fn (ActivityLog $log): array => [
                        'action' => $log->action,
                        'user' => $log->user?->name ?? 'Sistem',
                        'data' => $log->data ?? [],
                        'created_at' => $log->created_at,
                    ])->values()->all(),
                ];
            })->values()->all(),
            'reviews' => $reviews->map(fn (PerformanceReview $review): array => [
                'component' => $review->type === ReviewType::ManagerToEmployee ? 'Penilaian Atasan' : 'Umpan Balik Kinerja',
                'reviewer' => $review->reviewer?->name ?? 'Pengguna terhapus',
                'type' => $review->type->label(),
                'score' => $review->score,
                'submitted_at' => $review->submitted_at,
            ])->values()->all(),
            'discipline' => $dutyTrips->map(fn (DutyTrip $trip): array => [
                'destination' => $trip->destination,
                'starts_at' => $trip->starts_at,
                'attendance_status' => $trip->attendances->sortByDesc('captured_at')->first()?->status?->label() ?? 'Tidak hadir',
            ])->values()->all(),
        ];
    }

    public function verifyByManager(User $manager): void
    {
        DB::transaction(function () use ($manager): void {
            $result = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($result->employee->manager_id !== $manager->id || $manager->role !== UserRole::Manager
                || ! $result->calculated_at || $result->manager_verified_at || $result->published_at) {
                throw new BusinessRuleException('Hasil merit tidak dapat diverifikasi pengguna ini.');
            }

            $result->update(['manager_verified_by' => $manager->id, 'manager_verified_at' => now()]);
            ActivityLog::record('merit.manager_verified', $result, $manager);
            $this->setRawAttributes($result->getAttributes(), true);
        }, 3);
    }

    public function verifyByHr(User $hr): void
    {
        DB::transaction(function () use ($hr): void {
            $result = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($hr->role !== UserRole::Hr || ! $result->calculated_at || ! $result->manager_verified_at || $result->published_at) {
                throw new BusinessRuleException('Verifikasi Atasan wajib selesai sebelum verifikasi HR.');
            }

            $result->update(['hr_verified_by' => $hr->id, 'hr_verified_at' => now(), 'published_at' => now()]);
            ActivityLog::record('merit.hr_published', $result, $hr);
            $result->employee->notify(new MeritPublished($result));
            $this->setRawAttributes($result->getAttributes(), true);
        }, 3);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Employee => $query->where('employee_id', $user->id)->whereNotNull('published_at'),
            UserRole::Manager => $query->whereHas('employee', fn (Builder $query) => $query->where('manager_id', $user->id)),
            UserRole::Hr => $query,
        };
    }
}
