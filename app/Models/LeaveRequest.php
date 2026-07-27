<?php

namespace App\Models;

use App\Enums\AttendanceRequestStatus;
use App\Enums\DailySummaryStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\UserRole;
use App\Events\AttendanceDataChanged;
use App\Exceptions\BusinessRuleException;
use App\Notifications\LeaveRequestApproved;
use App\Notifications\LeaveRequestRejected;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class LeaveRequest extends Model
{
    private bool $statusTransitionAuthorized = false;

    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'type' => LeaveType::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => LeaveStatus::class,
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $leave): void {
            if (! $leave->exists) {
                $leave->status = LeaveStatus::Pending;
                $leave->approved_by = null;
                $leave->approved_at = null;
            }

            if ($leave->exists
                && $leave->getRawOriginal('status') !== LeaveStatus::Pending->value
                && $leave->isDirty()) {
                throw new BusinessRuleException('Cuti yang sudah diputuskan tidak dapat diubah.');
            }

            if ($leave->exists
                && $leave->isDirty('status')
                && ! $leave->statusTransitionAuthorized) {
                throw new BusinessRuleException('Status cuti hanya dapat diubah melalui aksi workflow.');
            }

            if ($leave->status === LeaveStatus::Approved
                && (! $leave->approved_by
                    || ! $leave->approved_at
                    || User::query()
                        ->whereKey($leave->approved_by)
                        ->where('role', UserRole::HrAdmin)
                        ->where('status', true)
                        ->doesntExist())) {
                throw new BusinessRuleException('Cuti approved wajib diverifikasi HR aktif beserta waktu approval.');
            }

            if ($leave->status !== LeaveStatus::Rejected) {
                // Cross-module overlap: reject if overlapping with approved attendance_requests
                // Blueprint Section 2A.2: (new_start < existing_end) AND (new_end > existing_start)
                $overlapping = AttendanceRequest::query()
                    ->where('user_id', $leave->user_id)
                    ->where('status', AttendanceRequestStatus::Approved)
                    ->where('duty_start_datetime', '<', $leave->end_date->copy()->addDay()) // leave end_date is inclusive (full day)
                    ->where('duty_end_datetime', '>', $leave->start_date->startOfDay())
                    ->exists();

                if ($overlapping) {
                    throw new BusinessRuleException('Cuti tumpang tindih dengan tugas luar yang sudah disetujui.');
                }

                $leaveOverlap = self::query()
                    ->where('user_id', $leave->user_id)
                    ->where('status', '!=', LeaveStatus::Rejected->value)
                    ->where('start_date', '<=', $leave->end_date)
                    ->where('end_date', '>=', $leave->start_date)
                    ->when($leave->exists, fn ($q) => $q->where('id', '!=', $leave->id))
                    ->exists();

                if ($leaveOverlap) {
                    throw new BusinessRuleException('Cuti tumpang tindih dengan pengajuan cuti lain.');
                }
            }
        });

        static::deleting(function (self $leave): void {
            if ($leave->status !== LeaveStatus::Pending) {
                throw new BusinessRuleException('Cuti yang sudah diputuskan tidak dapat dihapus.');
            }
        });

        static::saved(function (self $leave): void {
            // Blueprint Section 2A.6: On approval, auto-create/overwrite daily_attendance_summaries
            if ($leave->status !== LeaveStatus::Approved || ! $leave->approved_by) {
                return;
            }

            $period = $leave->start_date->toPeriod($leave->end_date);

            foreach ($period as $date) {
                DailyAttendanceSummary::updateOrCreate(
                    [
                        'user_id' => $leave->user_id,
                        'date' => $date->startOfDay(),
                    ],
                    [
                        'status' => DailySummaryStatus::Leave,
                        'attendance_request_id' => null,
                        'check_in_id' => null,
                        'check_out_id' => null,
                        'late_minutes' => 0,
                    ],
                );
            }

            AttendanceDataChanged::dispatch(
                $leave->user_id,
                $leave->start_date->toDateString(),
                $leave->end_date->toDateString(),
            );
        });

        static::updated(function (self $leave): void {
            if (! $leave->wasChanged('status')) {
                return;
            }

            match ($leave->status) {
                LeaveStatus::Approved => $leave->user->notify(new LeaveRequestApproved($leave)),
                LeaveStatus::Rejected => $leave->user->notify(new LeaveRequestRejected($leave)),
                default => null,
            };
        });
    }

    public function approve(User $actor): void
    {
        $this->decide($actor, LeaveStatus::Approved);
    }

    public function reject(User $actor): void
    {
        $this->decide($actor, LeaveStatus::Rejected);
    }

    private function decide(User $actor, LeaveStatus $status): void
    {
        DB::transaction(function () use ($actor, $status): void {
            $leave = self::query()->lockForUpdate()->findOrFail($this->getKey());
            $leave->ensurePendingHrDecision($actor);
            $leave->transitionTo($status, $status === LeaveStatus::Approved ? $actor : null);
        });

        $this->refresh();
    }

    private function transitionTo(LeaveStatus $status, ?User $approver): void
    {
        $this->statusTransitionAuthorized = true;

        try {
            $this->update([
                'status' => $status,
                'approved_by' => $approver?->id,
                'approved_at' => $approver ? now() : null,
            ]);
        } finally {
            $this->statusTransitionAuthorized = false;
        }
    }

    private function ensurePendingHrDecision(User $actor): void
    {
        if ($actor->role !== UserRole::HrAdmin || ! $actor->status || $this->status !== LeaveStatus::Pending) {
            throw new BusinessRuleException('Hanya HR aktif yang dapat memutuskan pengajuan cuti pending.');
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
