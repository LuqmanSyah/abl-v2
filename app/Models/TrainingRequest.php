<?php

namespace App\Models;

use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TrainingRequest extends Model
{
    use HasFactory;

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

            if (auth()->user()?->role === UserRole::Employee && $request->user_id !== auth()->id()) {
                throw new DomainException('Pegawai hanya dapat mengajukan pelatihan untuk dirinya sendiri.');
            }

            if (User::whereKey($request->user_id)->where('role', UserRole::Employee)->where('manager_id', $request->manager_id)->doesntExist()
                || User::whereKey($request->manager_id)->where('role', UserRole::Manager)->doesntExist()
                || Training::whereKey($request->training_id)->where('is_active', true)->doesntExist()) {
                throw new DomainException('Pengajuan pelatihan tidak valid.');
            }
        });
        static::created(fn (self $request) => ActivityLog::record('training.requested', $request));
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

    public function approveByManager(User $manager, ?string $notes = null): void
    {
        $this->transition(function (self $request) use ($manager, $notes): void {
            if ($manager->role !== UserRole::Manager || $request->manager_id !== $manager->id || $request->status !== TrainingRequestStatus::PendingManager) {
                throw new DomainException('Pengajuan pelatihan tidak dapat disetujui pengguna ini.');
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
        $this->transition(function (self $request) use ($employee, $reason): void {
            if ($employee->role !== UserRole::Employee || $request->user_id !== $employee->id
                || $request->status !== TrainingRequestStatus::Rejected || ! $employee->manager_id
                || User::whereKey($employee->manager_id)->where('role', UserRole::Manager)->where('is_active', true)->doesntExist()
                || Training::whereKey($request->training_id)->where('is_active', true)->doesntExist()) {
                throw new DomainException('Pengajuan pelatihan ini tidak dapat diajukan ulang.');
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
        $this->transition(function (self $request) use ($manager, $notes): void {
            if ($manager->role !== UserRole::Manager || $request->manager_id !== $manager->id || $request->status !== TrainingRequestStatus::PendingManager) {
                throw new DomainException('Pengajuan pelatihan tidak dapat ditolak pengguna ini.');
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
        $this->transition(function (self $request) use ($hr): void {
            if ($hr->role !== UserRole::Hr || $request->status !== TrainingRequestStatus::PendingHr) {
                throw new DomainException('Pengajuan pelatihan belum dapat diverifikasi HR.');
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
        $this->transition(function (self $request) use ($hr, $result): void {
            if ($hr->role !== UserRole::Hr || $request->status !== TrainingRequestStatus::Approved) {
                throw new DomainException('Pelatihan belum dapat diselesaikan.');
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

    private function transition(callable $transition): void
    {
        DB::transaction(function () use ($transition): void {
            $request = self::query()->lockForUpdate()->findOrFail($this->id);
            $transition($request);
            $this->setRawAttributes($request->getAttributes(), true);
        }, 3);
    }
}
