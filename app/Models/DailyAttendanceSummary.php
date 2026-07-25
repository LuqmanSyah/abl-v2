<?php

namespace App\Models;

use App\Enums\DailySummaryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyAttendanceSummary extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_request_id',
        'date',
        'check_in_id',
        'check_out_id',
        'status',
        'late_minutes',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => DailySummaryStatus::class,
        'late_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRequest(): BelongsTo
    {
        return $this->belongsTo(AttendanceRequest::class);
    }

    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'check_in_id');
    }

    public function checkOut(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'check_out_id');
    }
}
