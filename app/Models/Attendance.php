<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid', 'duty_trip_id', 'employee_id', 'captured_at', 'latitude', 'longitude',
        'accuracy_meters', 'distance_meters', 'photo_path', 'status', 'mock_location_suspected', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime', 'synced_at' => 'datetime', 'status' => AttendanceStatus::class,
            'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'mock_location_suspected' => 'boolean',
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
}
