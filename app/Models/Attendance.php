<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Events\AttendanceDataChanged;
use App\Exceptions\BusinessRuleException;
use App\Notifications\CheckOutExceptionApproved;
use App\Notifications\CheckOutExceptionPending;
use App\Notifications\CheckOutExceptionRejected;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Attendance extends Model
{
    private bool $statusTransitionAuthorized = false;

    protected $fillable = [
        'user_id',
        'attendance_request_id',
        'attendance_date',
        'session_key',
        'type',
        'latitude',
        'longitude',
        'distance_to_target_meters',
        'is_fallback',
        'address_snapshot',
        'photo_path',
        'is_radius_exception',
        'exception_reason',
        'status',
        'recorded_at',
    ];

    protected $casts = [
        'type' => AttendanceType::class,
        'attendance_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'distance_to_target_meters' => 'decimal:2',
        'is_fallback' => 'boolean',
        'is_radius_exception' => 'boolean',
        'status' => AttendanceStatus::class,
        'recorded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $attendance): void {
            if ($attendance->exists
                && $attendance->isDirty('status')
                && ! $attendance->statusTransitionAuthorized) {
                throw new BusinessRuleException('Status presensi hanya dapat diubah melalui aksi workflow.');
            }
        });

        static::creating(function (self $attendance): void {
            $attendance->recorded_at ??= now();
            $attendance->attendance_date = $attendance->recorded_at->toDateString();
            $attendance->session_key = $attendance->attendance_request_id
                ? 'request:'.$attendance->attendance_request_id
                : 'office';

            if ($attendance->type === AttendanceType::CheckIn
                && self::query()
                    ->where('user_id', $attendance->user_id)
                    ->where('attendance_date', $attendance->attendance_date)
                    ->where('session_key', $attendance->session_key)
                    ->where('type', AttendanceType::CheckIn)
                    ->exists()) {
                throw new BusinessRuleException('Check-in untuk sesi ini sudah tercatat hari ini.');
            }
        });

        static::saved(function (self $attendance): void {
            if ($attendance->type === AttendanceType::CheckOut
                && $attendance->status !== AttendanceStatus::PendingVerification) {
                static::dispatchMeritRecalculation($attendance);
            }
        });
        static::deleted(fn (self $attendance) => static::dispatchMeritRecalculation($attendance));

        static::created(function (self $attendance): void {
            if ($attendance->status !== AttendanceStatus::PendingVerification) {
                return;
            }

            if ($manager = $attendance->user->manager) {
                $manager->notify(new CheckOutExceptionPending($attendance));
            }
        });

        static::updated(function (self $attendance): void {
            if (! $attendance->is_radius_exception || ! $attendance->wasChanged('status')) {
                return;
            }

            match ($attendance->status) {
                AttendanceStatus::Normal => $attendance->user->notify(new CheckOutExceptionApproved($attendance)),
                AttendanceStatus::Rejected => $attendance->user->notify(new CheckOutExceptionRejected($attendance)),
                default => null,
            };
        });
    }

    private static function dispatchMeritRecalculation(self $attendance): void
    {
        $date = $attendance->attendance_date->toDateString();

        AttendanceDataChanged::dispatch($attendance->user_id, $date, $date, true);
    }

    public function canBeVerifiedBy(?User $actor): bool
    {
        return $actor?->role === UserRole::Manager
            && $actor->status
            && $this->status === AttendanceStatus::PendingVerification
            && $this->type === AttendanceType::CheckOut
            && $this->is_radius_exception
            && $this->attendance_date->toDateString() === now('Asia/Jakarta')->toDateString()
            && $this->user()->where('manager_id', $actor->id)->exists();
    }

    public function canViewEvidence(?User $actor): bool
    {
        return $actor?->status
            && ($actor->id === $this->user_id
                || $actor->role === UserRole::HrAdmin
                || ($actor->role === UserRole::Manager
                    && $this->user()->where('manager_id', $actor->id)->exists()));
    }

    public function approveException(User $actor): void
    {
        $this->verifyException($actor, AttendanceStatus::Normal);
    }

    public function rejectException(User $actor): void
    {
        $this->verifyException($actor, AttendanceStatus::Rejected);
    }

    public function timeoutException(): void
    {
        DB::transaction(function (): void {
            $attendance = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($attendance->status !== AttendanceStatus::PendingVerification) {
                return;
            }

            if ($attendance->type !== AttendanceType::CheckOut
                || ! $attendance->is_radius_exception) {
                throw new BusinessRuleException('Hanya exception check-out pending yang dapat ditutup saat cutoff.');
            }

            $attendance->transitionTo(AttendanceStatus::Rejected);
        });

        $this->refresh();
    }

    private function verifyException(User $actor, AttendanceStatus $status): void
    {
        DB::transaction(function () use ($actor, $status): void {
            $attendance = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! $attendance->canBeVerifiedBy($actor)) {
                throw new BusinessRuleException('Exception hanya dapat diputuskan atasan langsung sebelum cutoff.');
            }

            $attendance->transitionTo($status);
        });

        $this->refresh();
    }

    private function transitionTo(AttendanceStatus $status): void
    {
        $this->statusTransitionAuthorized = true;

        try {
            $this->update(['status' => $status]);
        } finally {
            $this->statusTransitionAuthorized = false;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRequest(): BelongsTo
    {
        return $this->belongsTo(AttendanceRequest::class);
    }
}
