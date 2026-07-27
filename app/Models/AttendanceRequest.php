<?php

namespace App\Models;

use App\Enums\AttendanceRequestStatus;
use App\Enums\FlowType;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Notifications\AttendanceRequestApproved;
use App\Notifications\AttendanceRequestAssigned;
use App\Notifications\AttendanceRequestCancelled;
use App\Notifications\AttendanceRequestRejected;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AttendanceRequest extends Model
{
    private bool $statusTransitionAuthorized = false;

    protected $fillable = [
        'user_id',
        'created_by',
        'flow_type',
        'destination_name',
        'destination_address',
        'target_latitude',
        'target_longitude',
        'allowed_radius_meters',
        'duty_start_datetime',
        'duty_end_datetime',
        'reason',
        'status',
        'approved_by',
    ];

    protected $casts = [
        'flow_type' => FlowType::class,
        'target_latitude' => 'decimal:7',
        'target_longitude' => 'decimal:7',
        'allowed_radius_meters' => 'integer',
        'duty_start_datetime' => 'datetime',
        'duty_end_datetime' => 'datetime',
        'status' => AttendanceRequestStatus::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $request): void {
            $user = User::query()->findOrFail($request->user_id);

            if (! $request->exists) {
                $request->status = $request->flow_type === FlowType::TopDown
                    ? AttendanceRequestStatus::Approved
                    : AttendanceRequestStatus::Pending;
                $request->approved_by = $request->flow_type === FlowType::TopDown
                    ? $request->created_by
                    : null;
            }

            if ($request->flow_type === FlowType::BottomUp
                && ($request->created_by !== $request->user_id || $user->role !== UserRole::Employee)) {
                throw new BusinessRuleException('Tugas luar bottom-up hanya dapat dibuat untuk diri sendiri.');
            }

            if ($request->flow_type === FlowType::TopDown && $request->created_by !== $user->manager_id) {
                throw new BusinessRuleException('Tugas luar top-down hanya dapat dibuat oleh atasan langsung.');
            }

            if ($request->exists
                && $request->getRawOriginal('status') !== AttendanceRequestStatus::Pending->value
                && $request->isDirty()) {
                throw new BusinessRuleException('Tugas luar yang sudah diputuskan tidak dapat diubah.');
            }

            if ($request->exists
                && $request->isDirty('status')
                && ! $request->statusTransitionAuthorized) {
                throw new BusinessRuleException('Status tugas luar hanya dapat diubah melalui aksi workflow.');
            }

            if (! in_array($request->status, [
                AttendanceRequestStatus::Rejected,
                AttendanceRequestStatus::Cancelled,
            ], true)) {
                // Blueprint Section 2A.2: Full Cross-Module Overlap Validation
                // Overlap ⟺ (new_start < existing_end) AND (new_end > existing_start)
                $arOverlap = self::query()
                    ->where('user_id', $request->user_id)
                    ->whereNotIn('status', [
                        AttendanceRequestStatus::Rejected,
                        AttendanceRequestStatus::Cancelled,
                    ])
                    ->where('duty_start_datetime', '<', $request->duty_end_datetime)
                    ->where('duty_end_datetime', '>', $request->duty_start_datetime)
                    ->when($request->exists, fn ($q) => $q->where('id', '!=', $request->id))
                    ->exists();

                if ($arOverlap) {
                    throw new BusinessRuleException('Tugas luar tumpang tindih dengan tugas luar lain.');
                }

                $leaveOverlap = LeaveRequest::query()
                    ->where('user_id', $request->user_id)
                    ->where('status', LeaveStatus::Approved)
                    ->whereDate('start_date', '<=', $request->duty_end_datetime)
                    ->whereDate('end_date', '>=', $request->duty_start_datetime)
                    ->exists();

                if ($leaveOverlap) {
                    throw new BusinessRuleException('Tugas luar tumpang tindih dengan cuti yang sudah disetujui.');
                }
            }

            if ($request->status === AttendanceRequestStatus::Approved
                && $request->approved_by !== $user->manager_id) {
                throw new BusinessRuleException('Tugas luar hanya dapat disetujui oleh atasan langsung.');
            }
        });

        static::deleting(function (self $request): void {
            if ($request->status !== AttendanceRequestStatus::Pending) {
                throw new BusinessRuleException('Tugas luar yang sudah diputuskan tidak dapat dihapus.');
            }
        });

        static::created(function (self $request): void {
            if ($request->flow_type === FlowType::TopDown) {
                $request->user->notify(new AttendanceRequestAssigned($request));
            }
        });

        static::updated(function (self $request): void {
            if (! $request->wasChanged('status')) {
                return;
            }

            match ($request->status) {
                AttendanceRequestStatus::Approved => $request->user->notify(new AttendanceRequestApproved($request)),
                AttendanceRequestStatus::Rejected => $request->user->notify(new AttendanceRequestRejected($request)),
                AttendanceRequestStatus::Cancelled => $request->user->notify(new AttendanceRequestCancelled($request)),
                default => null,
            };
        });
    }

    public function canBeDecidedBy(?User $actor): bool
    {
        return $actor?->role === UserRole::Manager
            && $actor->status
            && $this->flow_type === FlowType::BottomUp
            && $this->status === AttendanceRequestStatus::Pending
            && $this->user()->where('manager_id', $actor->id)->exists();
    }

    public function approve(User $actor): void
    {
        $this->decide($actor, AttendanceRequestStatus::Approved);
    }

    public function reject(User $actor): void
    {
        $this->decide($actor, AttendanceRequestStatus::Rejected);
    }

    public function canBeCancelledBy(?User $actor): bool
    {
        return $actor?->status
            && $actor->id === $this->user_id
            && $actor->id === $this->created_by
            && $this->flow_type === FlowType::BottomUp
            && $this->status === AttendanceRequestStatus::Pending;
    }

    public function cancel(User $actor): void
    {
        DB::transaction(function () use ($actor): void {
            $request = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! $request->canBeCancelledBy($actor)) {
                throw new BusinessRuleException('Hanya pemilik yang dapat membatalkan tugas luar pending.');
            }

            $request->transitionTo(AttendanceRequestStatus::Cancelled);
        });

        $this->refresh();
    }

    private function decide(User $actor, AttendanceRequestStatus $status): void
    {
        DB::transaction(function () use ($actor, $status): void {
            $request = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! $request->canBeDecidedBy($actor)) {
                throw new BusinessRuleException('Hanya atasan langsung yang dapat memutuskan tugas luar ini.');
            }

            $request->transitionTo(
                $status,
                $status === AttendanceRequestStatus::Approved ? $actor->id : null,
            );
        });

        $this->refresh();
    }

    private function transitionTo(AttendanceRequestStatus $status, ?int $approvedBy = null): void
    {
        $this->statusTransitionAuthorized = true;

        try {
            $this->update([
                'status' => $status,
                'approved_by' => $approvedBy,
            ]);
        } finally {
            $this->statusTransitionAuthorized = false;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function dailyAttendanceSummaries(): HasMany
    {
        return $this->hasMany(DailyAttendanceSummary::class);
    }
}
