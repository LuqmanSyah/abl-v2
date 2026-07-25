<?php

namespace App\Models;

use App\Enums\AttendanceRequestStatus;
use App\Enums\FlowType;
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
