<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'duty_trip_id', 'employee_id', 'attendance_date', 'captured_at', 'latitude', 'longitude',
        'accuracy_meters', 'distance_meters', 'photo_path', 'status', 'review_reason',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date', 'captured_at' => 'datetime', 'status' => AttendanceStatus::class,
            'latitude' => 'decimal:7', 'longitude' => 'decimal:7',
        ];
    }

    public function dutyTrip(): BelongsTo
    {
        return $this->belongsTo(DutyTrip::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function verifyByHr(User $hr): void
    {
        DB::transaction(function () use ($hr): void {
            $attendance = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($hr->role !== UserRole::Hr || $attendance->status !== AttendanceStatus::NeedsReview) {
                throw new BusinessRuleException('Absensi tidak dapat diverifikasi pengguna ini.');
            }

            $attendance->update(['status' => AttendanceStatus::Valid]);
            ActivityLog::record('attendance.verified', $attendance, $hr);
            $this->setRawAttributes($attendance->getAttributes(), true);
        }, 3);
    }
}
