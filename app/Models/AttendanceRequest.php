<?php

namespace App\Models;

use App\Enums\AttendanceRequestStatus;
use App\Enums\FlowType;
use App\Enums\LeaveStatus;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRequest extends Model
{
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
            // Blueprint Section 2A.2: Full Cross-Module Overlap Validation
            // Overlap ⟺ (new_start < existing_end) AND (new_end > existing_start)

            // 1. Overlap with other attendance_requests (not rejected/cancelled)
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

            // 2. Overlap with approved leave_requests
            $leaveOverlap = LeaveRequest::query()
                ->where('user_id', $request->user_id)
                ->where('status', LeaveStatus::Approved)
                ->where('start_date', '<', $request->duty_end_datetime->toDateString())
                ->where('end_date', '>=', $request->duty_start_datetime->toDateString())
                ->exists();

            if ($leaveOverlap) {
                throw new BusinessRuleException('Tugas luar tumpang tindih dengan cuti yang sudah disetujui.');
            }

            // Blueprint Section 2A.2: Top-down auto-approve
            if (! $request->exists && $request->flow_type === FlowType::TopDown) {
                $request->status = AttendanceRequestStatus::Approved;
                $request->approved_by = $request->created_by;
            }
        });
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
