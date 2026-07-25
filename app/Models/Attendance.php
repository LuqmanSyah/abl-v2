<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_request_id',
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
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'distance_to_target_meters' => 'decimal:2',
        'is_fallback' => 'boolean',
        'is_radius_exception' => 'boolean',
        'status' => AttendanceStatus::class,
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRequest(): BelongsTo
    {
        return $this->belongsTo(AttendanceRequest::class);
    }
}
