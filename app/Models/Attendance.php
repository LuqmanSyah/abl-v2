<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Events\AttendanceDataChanged;
use App\Exceptions\BusinessRuleException;
use App\Notifications\CheckOutExceptionPending;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;

class Attendance extends Model
{
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

        static::saved(fn (self $attendance) => static::dispatchMeritRecalculation($attendance));
        static::deleted(fn (self $attendance) => static::dispatchMeritRecalculation($attendance));

        static::created(function (self $attendance): void {
            if ($attendance->status !== AttendanceStatus::PendingVerification) {
                return;
            }

            $recipients = User::query()
                ->where('role', UserRole::HrAdmin)
                ->where('status', true)
                ->get();

            if ($manager = $attendance->user->manager) {
                $recipients->push($manager);
            }

            Notification::send($recipients, new CheckOutExceptionPending($attendance));
        });
    }

    private static function dispatchMeritRecalculation(self $attendance): void
    {
        $date = $attendance->attendance_date->toDateString();

        AttendanceDataChanged::dispatch($attendance->user_id, $date, $date, true);
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
