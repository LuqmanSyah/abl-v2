<?php

namespace App\Models;

use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Concerns\HasWorkflow;
use App\Notifications\TrainingPending;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TrainingRequest extends Model
{
    use HasFactory, HasWorkflow;

    private bool $managerRecommendation = false;

    protected $fillable = [
        'user_id', 'training_id', 'manager_id', 'status', 'reason', 'manager_notes', 'hr_result',
        'requested_at', 'manager_decided_at', 'hr_verified_by', 'hr_verified_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TrainingRequestStatus::class,
            'requested_at' => 'datetime',
            'manager_decided_at' => 'datetime',
            'hr_verified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            $request->status ??= TrainingRequestStatus::PendingManager;
            $request->requested_at ??= now();

            if (auth()->user()?->role === UserRole::Employee
                && ($request->user_id !== auth()->id() || $request->status !== TrainingRequestStatus::PendingManager)) {
                throw new BusinessRuleException('Pegawai hanya dapat mengajukan pelatihan untuk dirinya sendiri.');
            }

            if ($request->status === TrainingRequestStatus::Approved && ! $request->managerRecommendation) {
                throw new BusinessRuleException('Rekomendasi pelatihan Atasan harus dibuat melalui aksi rekomendasi.');
            }

            if (User::whereKey($request->user_id)->where('role', UserRole::Employee)->where('is_active', true)->where('manager_id', $request->manager_id)->doesntExist()
                || User::whereKey($request->manager_id)->where('role', UserRole::Manager)->where('is_active', true)->doesntExist()
                || Training::whereKey($request->training_id)->where('is_active', true)->doesntExist()) {
                throw new BusinessRuleException('Pengajuan pelatihan tidak valid.');
            }
        });
        static::created(function (self $request): void {
            if (! $request->managerRecommendation) {
                ActivityLog::record('training.requested', $request);
                $request->manager->notify(new TrainingPending($request));
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public static function recommendByManager(
        User $manager,
        User $employee,
        Training $training,
        MeritResult $meritResult,
        string $reason,
    ): self {
        $reason = trim($reason);

        if (! $reason
            || User::whereKey($manager->id)->where('role', UserRole::Manager)->where('is_active', true)->doesntExist()
            || User::whereKey($employee->id)->where('role', UserRole::Employee)->where('is_active', true)->where('manager_id', $manager->id)->doesntExist()
            || Training::whereKey($training->id)->where('is_active', true)->doesntExist()
            || MeritResult::whereKey($meritResult->id)->where('employee_id', $employee->id)->whereNotNull('published_at')->doesntExist()) {
            throw new BusinessRuleException('Rekomendasi pelatihan tidak valid.');
        }

        return DB::transaction(function () use ($manager, $employee, $training, $meritResult, $reason): self {
            if (self::where('user_id', $employee->id)->where('training_id', $training->id)->exists()) {
                throw new BusinessRuleException('Pelatihan ini sudah pernah diajukan atau direkomendasikan untuk pegawai tersebut.');
            }

            $request = new self([
                'user_id' => $employee->id,
                'training_id' => $training->id,
                'manager_id' => $manager->id,
                'status' => TrainingRequestStatus::Approved,
                'reason' => $reason,
                'requested_at' => now(),
                'manager_decided_at' => now(),
            ]);
            $request->managerRecommendation = true;
            $request->save();

            $period = $meritResult->reviewPeriod;
            ActivityLog::record('training.recommended', $request, $manager, [
                'merit_result_id' => $meritResult->id,
                'review_period_id' => $meritResult->review_period_id,
                'review_period' => $period->name,
                'kpi_score' => $meritResult->kpi_score,
                'discipline_score' => $meritResult->discipline_score,
                'manager_score' => $meritResult->manager_score,
                'review_360_score' => $meritResult->review_360_score,
                'total_score' => $meritResult->total_score,
                'estimated_bonus' => $meritResult->estimated_bonus,
            ]);

            return $request;
        }, 3);
    }

    public function approveByManager(User $manager, ?string $notes = null): void
    {
        $this->workflowTransition(function (self $request) use ($manager, $notes): void {
            if ($request->status !== TrainingRequestStatus::PendingManager
                || ! $this->actorIsManager($manager, $request)) {
                throw new BusinessRuleException('Pengajuan pelatihan tidak dapat disetujui pengguna ini.');
            }

            $request->update([
                'status' => TrainingRequestStatus::PendingHr,
                'manager_notes' => $notes,
                'manager_decided_at' => now(),
            ]);
            ActivityLog::record('training.manager_approved', $request, $manager);
        });
    }

    public function resubmit(User $employee, string $reason): void
    {
        $this->workflowTransition(function (self $request) use ($employee, $reason): void {
            if ($employee->role !== UserRole::Employee || $request->user_id !== $employee->id
                || $request->status !== TrainingRequestStatus::Rejected || ! $employee->manager_id
                || User::whereKey($employee->manager_id)->where('role', UserRole::Manager)->where('is_active', true)->doesntExist()
                || Training::whereKey($request->training_id)->where('is_active', true)->doesntExist()) {
                throw new BusinessRuleException('Pengajuan pelatihan ini tidak dapat diajukan ulang.');
            }

            $request->update([
                'manager_id' => $employee->manager_id,
                'status' => TrainingRequestStatus::PendingManager,
                'reason' => $reason,
                'manager_notes' => null,
                'manager_decided_at' => null,
                'requested_at' => now(),
            ]);
            ActivityLog::record('training.resubmitted', $request, $employee);
        });
    }

    public function rejectByManager(User $manager, string $notes): void
    {
        $this->workflowTransition(function (self $request) use ($manager, $notes): void {
            if ($request->status !== TrainingRequestStatus::PendingManager
                || ! $this->actorIsManager($manager, $request)) {
                throw new BusinessRuleException('Pengajuan pelatihan tidak dapat ditolak pengguna ini.');
            }

            $request->update([
                'status' => TrainingRequestStatus::Rejected,
                'manager_notes' => $notes,
                'manager_decided_at' => now(),
            ]);
            ActivityLog::record('training.manager_rejected', $request, $manager);
        });
    }

    public function verifyByHr(User $hr): void
    {
        $this->workflowTransition(function (self $request) use ($hr): void {
            if ($hr->role !== UserRole::Hr || $request->status !== TrainingRequestStatus::PendingHr) {
                throw new BusinessRuleException('Pengajuan pelatihan belum dapat diverifikasi HR.');
            }

            $request->update([
                'status' => TrainingRequestStatus::Approved,
                'hr_verified_by' => $hr->id,
                'hr_verified_at' => now(),
            ]);
            ActivityLog::record('training.hr_verified', $request, $hr);
        });
    }

    public function complete(User $hr, string $result): void
    {
        $this->workflowTransition(function (self $request) use ($hr, $result): void {
            if ($hr->role !== UserRole::Hr || $request->status !== TrainingRequestStatus::Approved) {
                throw new BusinessRuleException('Pelatihan belum dapat diselesaikan.');
            }

            $request->update([
                'status' => TrainingRequestStatus::Completed,
                'hr_result' => $result,
                'completed_at' => now(),
            ]);
            ActivityLog::record('training.completed', $request, $hr);
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Employee => $query->where('user_id', $user->id),
            UserRole::Manager => $query->where('manager_id', $user->id),
            UserRole::Hr => $query,
        };
    }

    private function actorIsManager(User $user, self $request): bool
    {
        if ($user->role !== UserRole::Manager) {
            return false;
        }

        if ($request->manager_id === $user->id) {
            return true;
        }

        if ($user->delegate_id === $request->manager_id) {
            ActivityLog::record('training.delegated', $request, $user, [
                'action' => 'delegated_approval',
                'delegate_of' => $request->manager_id,
            ]);

            return true;
        }

        return false;
    }
}
