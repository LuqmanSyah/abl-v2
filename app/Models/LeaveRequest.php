<?php

namespace App\Models;

use App\Enums\AttendanceRequestStatus;
use App\Enums\DailySummaryStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
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
            // Cross-module overlap: reject if overlapping with approved attendance_requests
            // Blueprint Section 2A.2: (new_start < existing_end) AND (new_end > existing_start)
            $overlapping = AttendanceRequest::query()
                ->where('user_id', $leave->user_id)
                ->where('status', AttendanceRequestStatus::Approved)
                ->where('duty_start_datetime', '<', $leave->end_date->copy()->addDay()) // leave end_date is inclusive (full day)
                ->where('duty_end_datetime', '>', $leave->start_date->startOfDay())
                ->when($leave->exists, fn ($q) => $q) // no self-exclusion needed, different table
                ->exists();

            if ($overlapping) {
                throw new BusinessRuleException('Cuti tumpang tindih dengan tugas luar yang sudah disetujui.');
            }

            // Overlap with other leave_requests for same user
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
        });
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
